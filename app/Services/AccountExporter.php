<?php

namespace App\Services;

use App\Http\Resources\DtcRecordResource;
use App\Http\Resources\RunResource;
use App\Http\Resources\TripResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\VehicleResource;
use App\Models\SharedRun;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * RGPD export: every piece of data stored about a user.
 */
class AccountExporter
{
    public function __construct(private Entitlements $entitlements) {}

    /**
     * @return array<string, mixed>
     */
    public function export(User $user, Request $request): array
    {
        return [
            'exported_at' => now()->toIso8601ZuluString(),
            'user' => [
                ...(new UserResource($user))->resolve($request),
                'email_verified_at' => $user->email_verified_at?->toIso8601ZuluString(),
                'created_at' => $user->created_at?->toIso8601ZuluString(),
                'last_synced_at' => $user->last_synced_at?->toIso8601ZuluString(),
            ],
            'entitlements' => $this->entitlements->toArray($user),
            'vehicles' => VehicleResource::collection($user->vehicles()->withTrashed()->orderBy('id')->get())->resolve($request),
            'runs' => RunResource::collection($user->runs()->withTrashed()->with('vehicle')->orderBy('id')->get())->resolve($request),
            'dtc_records' => DtcRecordResource::collection($user->dtcRecords()->withTrashed()->with('vehicle')->orderBy('id')->get())->resolve($request),
            'trips' => TripResource::collection($user->trips()->withTrashed()->with('vehicle')->orderBy('id')->get())->resolve($request),
            'shared_runs' => $user->sharedRuns()->with('run:id,uuid')->orderBy('shared_runs.id')->get()
                ->map(fn (SharedRun $share): array => [
                    'run_uuid' => $share->run->uuid,
                    'url' => $share->url(),
                    'recipient' => $share->recipient,
                    'views' => $share->views,
                    'created_at' => $share->created_at?->toIso8601ZuluString(),
                    'revoked_at' => $share->revoked_at?->toIso8601ZuluString(),
                ])->all(),
        ];
    }
}
