<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AccountExporter;
use App\Services\Entitlements;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MeController extends Controller
{
    /**
     * The authenticated user and their entitlements.
     */
    public function show(Request $request, Entitlements $entitlements): JsonResponse
    {
        return response()->json([
            'user' => (new UserResource($request->user()))->resolve($request),
            'entitlements' => $entitlements->toArray($request->user()),
        ]);
    }

    /**
     * Entitlements only (kept for older app builds).
     */
    public function entitlements(Request $request, Entitlements $entitlements): JsonResponse
    {
        return response()->json($entitlements->toArray($request->user()));
    }

    /**
     * RGPD export of every piece of data stored about the user.
     */
    public function export(Request $request, AccountExporter $exporter): JsonResponse
    {
        return response()->json($exporter->export($request->user(), $request));
    }

    /**
     * RGPD account deletion: removes the user and all of their data.
     */
    public function destroy(Request $request): Response
    {
        $request->user()->delete();

        return response()->noContent();
    }
}
