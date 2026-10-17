<?php

namespace App\Http\Controllers;

use App\Models\Run;
use App\Models\SharedRun;
use App\Models\User;
use App\Support\WebPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShareController extends Controller
{
    /**
     * List the user's share links.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $shares = $user->sharedRuns()
            ->with('run')
            ->latest('shared_runs.created_at')
            ->get()
            ->map(fn (SharedRun $share): array => WebPresenter::shareSummary($share));

        return Inertia::render('shares/Index', [
            'active' => $shares->whereNull('revoked_at')->values(),
            'revoked' => $shares->whereNotNull('revoked_at')->take(20)->values(),
        ]);
    }

    /**
     * Revoke one share link.
     */
    public function destroy(SharedRun $sharedRun): RedirectResponse
    {
        Gate::authorize('delete', $sharedRun);

        $sharedRun->update(['revoked_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lien révoqué.']);

        return back();
    }

    /**
     * Public, read-only view of a shared run.
     */
    public function show(string $token): Response
    {
        /** @var SharedRun|null $share */
        $share = SharedRun::query()->active()->where('token', $token)->with('run.vehicle', 'run.user')->first();

        abort_if($share === null, 404);

        $share->increment('views');

        /** @var Run $run */
        $run = $share->run;

        return Inertia::render('public/SharedRun', [
            'run' => [
                ...WebPresenter::runSummary($run),
                'distance_m' => $run->distance_m,
                'sixty_ft_s' => $run->sixty_ft_s,
                'splits' => $run->splits ?? [],
                'curve' => $run->curve ?? [],
                'peak_rpm' => $run->peak_rpm,
                'conditions' => $run->conditions ?? [],
            ],
            'driver' => $run->user->firstName(),
        ]);
    }
}
