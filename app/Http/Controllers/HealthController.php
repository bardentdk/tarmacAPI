<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\WebPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HealthController extends Controller
{
    /**
     * Trips and health score trend.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $filters = $request->validate([
            'vehicle' => ['nullable', 'uuid'],
        ]);

        $vehicles = $user->vehicles()->orderBy('name')->get();
        $vehicle = isset($filters['vehicle'])
            ? $vehicles->firstWhere('uuid', strtolower($filters['vehicle']))
            : $vehicles->first();

        $trips = $vehicle === null
            ? collect()
            : $vehicle->trips()->latest('started_at')->limit(60)->get();

        $latest = $trips->first();

        return Inertia::render('health/Index', [
            'vehicles' => $vehicles->map(fn (Vehicle $vehicle): array => WebPresenter::vehicleSummary($vehicle)),
            'vehicle' => $vehicle === null ? null : WebPresenter::vehicleSummary($vehicle),
            'trips' => $trips->reverse()->values()->map(fn (Trip $trip): array => WebPresenter::tripSummary($trip)),
            'latest' => $latest === null ? null : WebPresenter::tripSummary($latest),
            'averageScore' => $trips->isEmpty() ? null : (int) round($trips->avg('health_score')),
            'activeDtcCount' => $vehicle?->dtcRecords()->whereNull('cleared_at')->count() ?? 0,
        ]);
    }
}
