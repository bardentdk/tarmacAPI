<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShareRunRequest;
use App\Models\Run;
use App\Services\RunSharing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RunShareController extends Controller
{
    public function __construct(private RunSharing $sharing) {}

    /**
     * Create (or return) the public read-only link of a run.
     */
    public function store(ShareRunRequest $request, string $uuid): JsonResponse
    {
        $share = $this->sharing->share($this->findRun($request, $uuid), $request->validated('recipient'));

        return response()->json(['url' => $share->url()]);
    }

    /**
     * Revoke the public link of a run.
     */
    public function destroy(Request $request, string $uuid): Response
    {
        $this->sharing->revoke($this->findRun($request, $uuid));

        return response()->noContent();
    }

    /**
     * Find a run owned by the authenticated user (404 otherwise).
     */
    private function findRun(Request $request, string $uuid): Run
    {
        /** @var Run */
        return $request->user()->runs()->where('uuid', strtolower($uuid))->firstOrFail();
    }
}
