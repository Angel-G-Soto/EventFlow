<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyApiKey
{
    /**
     * Middleware that authorizes calls using a single shared API key.
     * - Reads header: X-API-KEY
     * - Compares against env('API_KEY')
     * - Rejects with 401 if missing or mismatched
     */
    public function handle(Request $request, Closure $next)
    {
        // The key provided by the caller (HTTP client)
        $apiKeyHeader = (string) $request->header('X-API-KEY', '');

        // The key configured on the server
        $configuredKey = (string) env('API_KEY', '');

        // Timing-safe equality check prevents certain side-channel attacks
        if ($apiKeyHeader !== '' && $configuredKey !== '' && hash_equals($configuredKey, $apiKeyHeader)) {
            return $next($request);
        }

        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
}
