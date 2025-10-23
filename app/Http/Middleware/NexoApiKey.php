<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NexoApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = $request->header('X-API-KEY') ?? $request->query('api_key');

        if (!$provided || !hash_equals($provided, config('services.nexo.api_key'))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}

