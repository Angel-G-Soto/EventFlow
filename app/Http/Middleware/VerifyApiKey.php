<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next)
    {
        // Get the API key from the request header (e.g., 'X-API-KEY')
        $apiKey = $request->header('X-API-KEY');

        // Compare it to the one stored in .env file
        if ($apiKey && $apiKey === config('services.nexo.api_key')) {
            return $next($request); // Key is valid, proceed with the request.
        }

        // Key is missing or invalid, return an error.
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
}
