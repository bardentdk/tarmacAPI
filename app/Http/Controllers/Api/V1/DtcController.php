<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DtcLookupRequest;
use App\Services\Dtc\DtcExplainer;
use App\Services\Entitlements;
use Illuminate\Http\JsonResponse;

class DtcController extends Controller
{
    /**
     * Explain a trouble code (knowledge base, AI, or structural fallback).
     */
    public function show(DtcLookupRequest $request, DtcExplainer $explainer, Entitlements $entitlements): JsonResponse
    {
        return response()->json($explainer->explain(
            code: $request->validated('code'),
            make: $request->validated('make'),
            engine: $request->validated('engine'),
            allowAi: $entitlements->allows($request->user(), 'ai'),
        ));
    }
}
