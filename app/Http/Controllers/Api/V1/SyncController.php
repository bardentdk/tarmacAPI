<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SyncPullRequest;
use App\Http\Requests\Api\SyncRequest;
use App\Http\Resources\DtcRecordResource;
use App\Http\Resources\RunResource;
use App\Http\Resources\TripResource;
use App\Http\Resources\VehicleResource;
use App\Services\Sync\SyncService;
use Illuminate\Http\JsonResponse;

class SyncController extends Controller
{
    public function __construct(private SyncService $sync) {}

    /**
     * Upsert records pushed by the app (idempotent, last-write-wins).
     */
    public function push(SyncRequest $request): JsonResponse
    {
        return response()->json($this->sync->push($request->user(), $request->validated()));
    }

    /**
     * Records written on the server since `since` (restore on a new phone).
     */
    public function pull(SyncPullRequest $request): JsonResponse
    {
        $serverTime = now()->toIso8601ZuluString();
        $since = $request->validated('since') !== null ? $request->date('since') : null;
        $records = $this->sync->pull($request->user(), $since);

        return response()->json([
            'vehicles' => VehicleResource::collection($records['vehicles'])->resolve($request),
            'runs' => RunResource::collection($records['runs'])->resolve($request),
            'dtc_records' => DtcRecordResource::collection($records['dtc_records'])->resolve($request),
            'trips' => TripResource::collection($records['trips'])->resolve($request),
            'server_time' => $serverTime,
        ]);
    }
}
