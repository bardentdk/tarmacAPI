<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreReleaseRequest;
use App\Http\Resources\AppReleaseResource;
use App\Models\AppRelease;
use App\Services\Ota\ReleasePublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Publishing API used by the release script (bearer OTA_PUBLISH_TOKEN).
 */
class ReleaseController extends Controller
{
    /**
     * List releases, latest first.
     */
    public function index(): AnonymousResourceCollection
    {
        return AppReleaseResource::collection(
            AppRelease::query()->orderByDesc('published_at')->orderByDesc('id')->paginate(50),
        );
    }

    /**
     * Upload and publish a new OTA bundle or APK.
     */
    public function store(StoreReleaseRequest $request, ReleasePublisher $publisher): JsonResponse
    {
        /** @var array{kind: string, channel?: string|null, fingerprint: string, version: string, version_code?: int|string|null, notes?: string|null, mandatory?: bool|null, native_signature?: string|null} $attributes */
        $attributes = $request->safe()->except('file');

        $release = $publisher->publish($request->file('file'), $attributes);

        return response()->json(['release' => (new AppReleaseResource($release))->resolve($request)], 201);
    }

    /**
     * Stop offering a release (server-side rollback).
     */
    public function deactivate(Request $request, string $uuid): JsonResponse
    {
        return $this->setActive($request, $uuid, false);
    }

    /**
     * Offer a release again.
     */
    public function activate(Request $request, string $uuid): JsonResponse
    {
        return $this->setActive($request, $uuid, true);
    }

    /**
     * Toggle whether a release is offered to devices.
     */
    private function setActive(Request $request, string $uuid, bool $isActive): JsonResponse
    {
        $release = AppRelease::query()->where('uuid', strtolower($uuid))->firstOrFail();
        $release->update(['is_active' => $isActive]);

        return response()->json(['release' => (new AppReleaseResource($release))->resolve($request)]);
    }
}
