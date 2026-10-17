<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ReleaseChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OtaCheckRequest;
use App\Http\Resources\OtaReleaseResource;
use App\Services\Ota\ReleaseCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class OtaController extends Controller
{
    public function __construct(private ReleaseCatalog $catalog) {}

    /**
     * Tell the nativephp/mobile-ota plugin whether a newer bundle exists for its shell.
     */
    public function check(OtaCheckRequest $request, string $project, string $arc, string $fingerprint, ?string $held = null): JsonResponse
    {
        if (! $this->catalog->knowsProject($project)) {
            return response()->json(['message' => 'Projet inconnu.'], 404);
        }

        $shellBuiltAt = $request->validated('shell_built_at');
        $latest = $this->catalog->latestOta(ReleaseChannel::fromInput($arc), $fingerprint);

        $upToDate = $this->catalog->isUpToDate(
            $latest,
            $held,
            $shellBuiltAt !== null ? Carbon::parse($shellBuiltAt) : null,
        );

        if ($upToDate || $latest === null) {
            return response()->json(['up_to_date' => true, 'release' => null, 'download_url' => null]);
        }

        return response()->json([
            'up_to_date' => false,
            'release' => (new OtaReleaseResource($latest))->resolve($request),
            'download_url' => $this->catalog->downloadUrl($latest),
        ]);
    }
}
