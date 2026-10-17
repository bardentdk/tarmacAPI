<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the release publishing API with the OTA_PUBLISH_TOKEN bearer secret.
 */
class EnsureValidPublishToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('tarmac.ota.publish_token');

        if ($token === '') {
            return response()->json(['message' => 'Publication désactivée.'], 403);
        }

        if (! hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Non autorisé.'], 401);
        }

        return $next($request);
    }
}
