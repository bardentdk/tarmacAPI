<?php

namespace App\Http\Controllers;

use App\Enums\RunMode;
use App\Models\Run;
use App\Models\SharedRun;
use App\Models\User;
use App\Services\Entitlements;
use App\Support\WebPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the client space home.
     */
    public function __invoke(Request $request, Entitlements $entitlements): Response
    {
        /** @var User $user */
        $user = $request->user();

        $latestTrips = $user->trips()->latest('started_at')->limit(10)->get();
        $latestTrip = $latestTrips->first();
        $activeDtcCount = $user->dtcRecords()->whereNull('cleared_at')->count();

        $recentRuns = $user->runs()->withoutSamples()->with('vehicle')->latest('recorded_at')->limit(5)->get();
        $recentDtcs = $user->dtcRecords()->with('vehicle')->latest('read_at')->limit(5)->get();
        $definitions = WebPresenter::definitionsFor($recentDtcs->pluck('code'));

        $vehicles = $user->vehicles()->orderBy('name')->get();

        return Inertia::render('Dashboard', [
            'greeting' => now('Europe/Paris')->hour >= 18 || now('Europe/Paris')->hour < 5 ? 'Bonsoir' : 'Bonjour',
            'firstName' => $user->firstName(),
            'lastSyncedAt' => $user->last_synced_at?->toIso8601ZuluString(),
            'stats' => [
                'health' => $latestTrip === null ? null : [
                    'score' => $latestTrip->health_score,
                    'previous' => $latestTrips->get(1)?->health_score,
                    'watch_count' => $activeDtcCount + collect($latestTrip->components ?? [])
                        ->filter(fn (float $value): bool => $value < 0.8)
                        ->count(),
                ],
                'best_zero_100' => $this->bestRun($user, RunMode::ZeroHundred),
                'power' => $this->powerStat($user),
                'storage_bytes' => (int) $user->runs()->sum('samples_bytes'),
            ],
            'healthTrend' => $latestTrips->reverse()->values()->map(fn ($trip): array => [
                'date' => $trip->started_at->toIso8601ZuluString(),
                'score' => $trip->health_score,
            ]),
            'bestByMode' => $this->bestByMode($user),
            'recentRuns' => $recentRuns->map(fn (Run $run): array => WebPresenter::runSummary($run)),
            'recentDtcs' => $recentDtcs->map(fn ($record): array => WebPresenter::dtcSummary($record, $definitions)),
            'vehicles' => $vehicles->map(fn ($vehicle): array => WebPresenter::vehicleSummary($vehicle)),
            'activeShares' => $user->sharedRuns()->active()->with('run')->latest('shared_runs.created_at')->limit(5)->get()
                ->map(fn (SharedRun $share): array => WebPresenter::shareSummary($share)),
            'subscription' => [
                ...$entitlements->toArray($user),
                'plan_period' => $entitlements->hasActivePaidPlan($user) ? $user->plan_period : null,
            ],
        ]);
    }

    /**
     * @return array{uuid: string, result_s: float, recorded_at: string}|null
     */
    private function bestRun(User $user, RunMode $mode): ?array
    {
        $run = $user->runs()
            ->withoutSamples()
            ->where('mode', $mode->value)
            ->whereNotNull('result_s')
            ->orderBy('result_s')
            ->first();

        return $run === null ? null : [
            'uuid' => $run->uuid,
            'result_s' => (float) $run->result_s,
            'recorded_at' => $run->recorded_at->toIso8601ZuluString(),
        ];
    }

    /**
     * Best estimated power, compared with the first measured power.
     *
     * @return array{uuid: string, hp: float, delta_hp: float|null}|null
     */
    private function powerStat(User $user): ?array
    {
        $best = $user->runs()->withoutSamples()->whereNotNull('power_hp')->orderByDesc('power_hp')->first();

        if ($best === null) {
            return null;
        }

        $first = $user->runs()->withoutSamples()
            ->where('vehicle_id', $best->vehicle_id)
            ->whereNotNull('power_hp')
            ->orderBy('recorded_at')
            ->first();

        return [
            'uuid' => $best->uuid,
            'hp' => (float) $best->power_hp,
            'delta_hp' => $first !== null && $first->id !== $best->id ? round((float) $best->power_hp - (float) $first->power_hp, 1) : null,
        ];
    }

    /**
     * Best time per timed mode.
     *
     * @return list<array{mode: string, mode_label: string, result_s: float, uuid: string, recorded_at: string}>
     */
    private function bestByMode(User $user): array
    {
        $timedModes = array_values(array_filter(RunMode::cases(), fn (RunMode $mode): bool => $mode !== RunMode::Dyno));
        $best = [];

        $runs = $user->runs()
            ->withoutSamples()
            ->whereIn('mode', array_map(fn (RunMode $mode): string => $mode->value, $timedModes))
            ->whereNotNull('result_s')
            ->orderBy('result_s')
            ->get()
            ->unique('mode');

        foreach ($timedModes as $mode) {
            $run = $runs->firstWhere('mode', $mode->value);

            if ($run !== null) {
                $best[] = [
                    'mode' => $mode->value,
                    'mode_label' => $mode->label(),
                    'result_s' => (float) $run->result_s,
                    'uuid' => $run->uuid,
                    'recorded_at' => $run->recorded_at->toIso8601ZuluString(),
                ];
            }
        }

        return $best;
    }
}
