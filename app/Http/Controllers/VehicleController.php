<?php

namespace App\Http\Controllers;

use App\Enums\Drivetrain;
use App\Enums\Fuel;
use App\Enums\Gearbox;
use App\Enums\TuningStage;
use App\Http\Requests\StoreVehicleRequest;
use App\Models\DtcRecord;
use App\Models\Run;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Entitlements;
use App\Support\WebPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    /**
     * List the user's vehicles.
     */
    public function index(Request $request, Entitlements $entitlements): Response
    {
        /** @var User $user */
        $user = $request->user();

        $vehicles = $user->vehicles()
            ->withCount(['runs', 'trips', 'dtcRecords as active_dtc_count' => fn ($query) => $query->whereNull('cleared_at')])
            ->orderBy('name')
            ->get();

        $latestScores = Trip::query()
            ->whereIn('vehicle_id', $vehicles->modelKeys())
            ->orderByDesc('started_at')
            ->get(['vehicle_id', 'health_score', 'started_at'])
            ->unique('vehicle_id')
            ->keyBy('vehicle_id');

        return Inertia::render('vehicles/Index', [
            'vehicles' => $vehicles->map(fn (Vehicle $vehicle): array => [
                ...WebPresenter::vehicleSummary($vehicle),
                'runs_count' => $vehicle->runs_count,
                'trips_count' => $vehicle->trips_count,
                'active_dtc_count' => $vehicle->active_dtc_count,
                'health_score' => $latestScores->get($vehicle->id)?->health_score,
            ]),
            'maxVehicles' => $entitlements->features($user)['max_vehicles'],
        ]);
    }

    /**
     * Show the form to add a vehicle from the web.
     */
    public function create(): Response
    {
        return Inertia::render('vehicles/Create', [
            'options' => [
                'fuel' => $this->options(Fuel::cases()),
                'gearbox' => $this->options(Gearbox::cases()),
                'drivetrain' => $this->options(Drivetrain::cases()),
                'tuning_stage' => $this->options(TuningStage::cases()),
            ],
        ]);
    }

    /**
     * Store a vehicle created from the web (it reaches the phone on the next pull).
     */
    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $vehicle = new Vehicle([
            ...$request->validated(),
            'uuid' => (string) Str::uuid(),
            'wheel_radius_m' => $request->validated('wheel_radius_m') ?? 0.31,
            'cda' => $request->validated('cda') ?? 0.68,
            'redline_rpm' => $request->validated('redline_rpm') ?? 6500,
            'shift_light_start_rpm' => $request->validated('shift_light_start_rpm') ?? 6000,
            'k_cal' => 1.0,
        ]);
        $vehicle->user_id = $request->user()->id;
        $vehicle->synced_at = now();
        $vehicle->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Véhicule ajouté. Il arrive sur ton téléphone à la prochaine synchro.']);

        return to_route('vehicles.show', $vehicle);
    }

    /**
     * Show a vehicle with its runs, trouble codes and health trend.
     */
    public function show(Vehicle $vehicle): Response
    {
        Gate::authorize('view', $vehicle);

        $runs = $vehicle->runs()->withoutSamples()->latest('recorded_at')->limit(20)->get();
        $dtcRecords = $vehicle->dtcRecords()->latest('read_at')->get();
        $definitions = WebPresenter::definitionsFor($dtcRecords->pluck('code'));
        $trips = $vehicle->trips()->latest('started_at')->limit(30)->get();

        return Inertia::render('vehicles/Show', [
            'vehicle' => [
                ...WebPresenter::vehicleSummary($vehicle),
                'fuel' => Fuel::tryFrom($vehicle->fuel)?->label(),
                'gearbox' => Gearbox::tryFrom($vehicle->gearbox)?->label(),
                'drivetrain' => Drivetrain::tryFrom($vehicle->drivetrain)?->label(),
                'mass_kg' => $vehicle->mass_kg,
                'tire_size' => $vehicle->tire_size,
                'redline_rpm' => $vehicle->redline_rpm,
                'k_cal' => $vehicle->k_cal,
            ],
            'runs' => $runs->map(fn (Run $run): array => WebPresenter::runSummary($run)),
            'dtcRecords' => $dtcRecords->map(fn (DtcRecord $record): array => WebPresenter::dtcSummary($record, $definitions)),
            'trips' => $trips->reverse()->values()->map(fn (Trip $trip): array => WebPresenter::tripSummary($trip)),
        ]);
    }

    /**
     * @param  list<Fuel|Gearbox|Drivetrain|TuningStage>  $cases
     * @return list<array{value: string, label: string}>
     */
    private function options(array $cases): array
    {
        return array_map(fn (Fuel|Gearbox|Drivetrain|TuningStage $case): array => ['value' => $case->value, 'label' => $case->label()], $cases);
    }
}
