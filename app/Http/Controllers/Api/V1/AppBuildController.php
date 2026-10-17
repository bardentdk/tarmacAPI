<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ReleaseChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LatestAppBuildRequest;
use App\Http\Resources\AppBuildResource;
use App\Services\Ota\ReleaseCatalog;
use Illuminate\Http\JsonResponse;

class AppBuildController extends Controller
{
    public function __construct(private ReleaseCatalog $catalog) {}

    /**
     * Latest native Android build newer than the installed version code.
     */
    public function latest(LatestAppBuildRequest $request, string $project): JsonResponse
    {
        if (! $this->catalog->knowsProject($project)) {
            return response()->json(['message' => 'Projet inconnu.'], 404);
        }

        $installedVersionCode = (int) $request->validated('version_code', 0);
        $latest = $this->catalog->latestApk(ReleaseChannel::fromInput($request->validated('channel')), $installedVersionCode);

        if ($latest === null) {
            return response()->json(['available' => false, 'release' => null, 'download_url' => null]);
        }

        return response()->json([
            'available' => true,
            'release' => [
                ...(new AppBuildResource($latest))->resolve($request),
                'mandatory' => $this->catalog->apkUpdateIsMandatory($latest, $installedVersionCode),
            ],
            'download_url' => $this->catalog->downloadUrl($latest),
        ]);
    }
}
