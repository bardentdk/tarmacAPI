<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RevenueCatWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevenueCatWebhookController extends Controller
{
    /**
     * Handle a RevenueCat webhook event (authenticated by bearer secret).
     */
    public function __invoke(Request $request, RevenueCatWebhook $webhook): JsonResponse
    {
        $secret = (string) config('tarmac.revenuecat.webhook_secret');
        $token = (string) $request->bearerToken();

        if ($secret === '' || ! hash_equals($secret, $token)) {
            return response()->json(['message' => 'Non autorisé.'], 401);
        }

        $handled = $webhook->handle((array) $request->input('event', []));

        return response()->json(['handled' => $handled]);
    }
}
