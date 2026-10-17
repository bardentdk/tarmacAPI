<?php

namespace App\Http\Controllers;

use App\Enums\RunMode;
use App\Enums\RunSource;
use App\Http\Requests\ShareRunRequest;
use App\Models\Run;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Entitlements;
use App\Services\RunSharing;
use App\Support\WebPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RunController extends Controller
{
    /**
     * List runs, filterable by vehicle and mode.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $filters = $request->validate([
            'vehicle' => ['nullable', 'uuid'],
            'mode' => ['nullable', Rule::enum(RunMode::class)],
        ]);

        $vehicles = $user->vehicles()->orderBy('name')->get();
        $vehicleId = isset($filters['vehicle'])
            ? $vehicles->firstWhere('uuid', strtolower($filters['vehicle']))->id ?? 0
            : null;

        $runs = $user->runs()
            ->withoutSamples()
            ->with('vehicle')
            ->when($vehicleId !== null, fn ($query) => $query->where('vehicle_id', $vehicleId))
            ->when(isset($filters['mode']), fn ($query) => $query->where('mode', $filters['mode']))
            ->latest('recorded_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Run $run): array => WebPresenter::runSummary($run));

        return Inertia::render('runs/Index', [
            'runs' => $runs,
            'filters' => [
                'vehicle' => $filters['vehicle'] ?? null,
                'mode' => $filters['mode'] ?? null,
            ],
            'vehicles' => $vehicles->map(fn (Vehicle $vehicle): array => WebPresenter::vehicleSummary($vehicle)),
            'modes' => WebPresenter::modeOptions(),
        ]);
    }

    /**
     * Show the run report.
     */
    public function show(Request $request, Run $run, Entitlements $entitlements): Response
    {
        Gate::authorize('view', $run);

        $run->load(['vehicle', 'activeShare']);
        $features = $entitlements->features($request->user());

        return Inertia::render('runs/Show', [
            'run' => [
                ...WebPresenter::runSummary($run),
                'distance_m' => $run->distance_m,
                'sixty_ft_s' => $run->sixty_ft_s,
                'reaction_s' => $run->reaction_s,
                'splits' => $run->splits ?? [],
                'power_ci_hp' => $run->power_ci_hp,
                'peak_rpm' => $run->peak_rpm,
                'curve' => $features['power'] ? ($run->curve ?? []) : [],
                'conditions' => $run->conditions ?? [],
                'source_label' => RunSource::tryFrom($run->source)?->label() ?? $run->source,
                'accuracy_s' => $run->accuracy_s,
                'notes' => $run->notes,
                'has_samples' => $run->samples_bytes > 0,
            ],
            'share' => $run->activeShare === null ? null : WebPresenter::shareSummary($run->activeShare),
            'can' => [
                'see_power' => $features['power'],
                'download_csv' => $features['csv_logs'],
            ],
        ]);
    }

    /**
     * Download the run samples as CSV (Tuner plan and above).
     */
    public function csv(Request $request, Run $run, Entitlements $entitlements): StreamedResponse
    {
        Gate::authorize('view', $run);

        abort_unless($entitlements->allows($request->user(), 'csv_logs'), 403, 'Les logs CSV sont inclus dans l\'offre Tuner.');

        $samples = $run->samples ?? [];
        $filename = sprintf('tarmac-%s-%s.csv', $run->mode, $run->recorded_at->format('Ymd-His'));

        return response()->streamDownload(function () use ($samples): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['t_s', 'speed_kmh', 'rpm', 'distance_m'], ';');

            foreach ($samples as $sample) {
                fputcsv($handle, [
                    $sample['t'] ?? null,
                    $sample['v'] ?? null,
                    $sample['rpm'] ?? null,
                    $sample['d'] ?? null,
                ], ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Create (or keep) the public link of the run.
     */
    public function share(ShareRunRequest $request, Run $run, RunSharing $sharing): RedirectResponse
    {
        Gate::authorize('update', $run);

        $sharing->share($run, $request->validated('recipient'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lien de partage prêt.']);

        return back();
    }

    /**
     * Revoke the public link of the run.
     */
    public function unshare(Run $run, RunSharing $sharing): RedirectResponse
    {
        Gate::authorize('update', $run);

        $sharing->revoke($run);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lien révoqué.']);

        return back();
    }
}
