<?php

namespace App\Http\Controllers;

use App\Models\DtcRecord;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Dtc\DtcExplainer;
use App\Support\WebPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiagnosticController extends Controller
{
    /**
     * Trouble code history, with the explanation of the selected code.
     */
    public function index(Request $request, DtcExplainer $explainer): Response
    {
        /** @var User $user */
        $user = $request->user();

        $filters = $request->validate([
            'vehicle' => ['nullable', 'uuid'],
            'code' => ['nullable', 'string', 'regex:/^[PCBUpcbu][0-9A-Fa-f]{4}$/'],
        ]);

        $vehicles = $user->vehicles()->orderBy('name')->get();
        $vehicle = isset($filters['vehicle']) ? $vehicles->firstWhere('uuid', strtolower($filters['vehicle'])) : null;

        $records = $user->dtcRecords()
            ->with('vehicle')
            ->when(isset($filters['vehicle']), fn ($query) => $query->where('vehicle_id', $vehicle->id ?? 0))
            ->latest('read_at')
            ->paginate(25)
            ->withQueryString();

        $definitions = WebPresenter::definitionsFor(collect($records->items())->pluck('code'));

        $selected = null;

        if (isset($filters['code'])) {
            $code = strtoupper($filters['code']);
            $record = $user->dtcRecords()->with('vehicle')->where('code', $code)->latest('read_at')->first();
            $selected = $record === null ? $explainer->genericExplanation($code) : $this->explanationFor($explainer, $record);
        }

        return Inertia::render('diagnostics/Index', [
            'records' => $records->through(fn (DtcRecord $record): array => WebPresenter::dtcSummary($record, $definitions)),
            'activeCount' => $user->dtcRecords()->whereNull('cleared_at')->count(),
            'filters' => [
                'vehicle' => $filters['vehicle'] ?? null,
                'code' => isset($filters['code']) ? strtoupper($filters['code']) : null,
            ],
            'vehicles' => $vehicles->map(fn (Vehicle $vehicle): array => WebPresenter::vehicleSummary($vehicle)),
            'selected' => $selected,
        ]);
    }

    /**
     * Stored explanation for a record (knowledge base or cached AI answer, never a new AI call).
     *
     * @return array<string, mixed>
     */
    private function explanationFor(DtcExplainer $explainer, DtcRecord $record): array
    {
        $definition = $explainer->findDefinition(
            $record->code,
            $explainer->normalizeKey($record->vehicle->make),
            $explainer->normalizeKey($record->vehicle->engine),
        );

        return $definition === null ? $explainer->genericExplanation($record->code) : $explainer->present($definition);
    }
}
