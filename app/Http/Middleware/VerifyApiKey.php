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
        $apiKeyHeader  = (string) $request->header('X-API-KEY', '');
        $configuredKey = (string) env('API_KEY', '');

        dd([
            'apiKeyHeader'        => $apiKeyHeader,
            'apiKeyHeader_len'    => strlen($apiKeyHeader),
            'configuredKey'       => $configuredKey,
            'configuredKey_len'   => strlen($configuredKey),
            'configuredKey_type'  => gettype($configuredKey),
            'apiKeyHeader_type'   => gettype($apiKeyHeader),
            'hash_equals_result'  => hash_equals($configuredKey, $apiKeyHeader),
            'condition_result'    => ($apiKeyHeader !== '' && $configuredKey !== '' && hash_equals($configuredKey, $apiKeyHeader)),
        ]);
        
        // Success path
        if ($apiKeyHeader !== '' && $configuredKey !== '' && hash_equals($configuredKey, $apiKeyHeader)) {

            // AUDIT: any successful NEXO API interaction (best-effort, non-blocking)
            try {
                /** @var \App\Services\AuditService $audit */
                $audit = app(\App\Services\AuditService::class);

                // Pick an actor id: authenticated user if present, otherwise a service actor from config
                $actorId = auth()->id()
                    ?: (int) config('eventflow.nexo_actor_id', (int) config('eventflow.system_user_id', 0));

                if ($actorId > 0) {
                    // Minimal meta; we keep it lightweight on purpose
                    $meta = ['authorized' => true];

                    // Enrich with request context (ip/method/path/ua) automatically
                    $audit->logActionFromRequest(
                        $request,
                        $actorId,
                        'nexo',              // targetType
                        'NEXO_API_HIT',      // actionCode
                        (string) $request->path(), // targetId (endpoint path)
                        $meta
                    );
                }
            } catch (\Throwable $e) {
                // never block request on audit failures
                report($e);
            }

            return $next($request);
        }

        // Optional: audit denied attempts (toggleable via config)
        try {
            if (config('eventflow.audit_nexo_failures', false)) {
                /** @var \App\Services\AuditService $audit */
                $audit = app(\App\Services\AuditService::class);

                $actorId = (int) config('eventflow.nexo_actor_id', (int) config('eventflow.system_user_id', 0));
                if ($actorId > 0) {
                    $audit->logActionFromRequest(
                        $request,
                        $actorId,
                        'nexo',
                        'NEXO_API_DENIED',
                        (string) $request->path(),
                        ['authorized' => false]
                    );
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
}
