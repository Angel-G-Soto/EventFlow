<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request and verify the user's role.
     *
     * Example: ->middleware('role:system-admin')
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        // Allow comma or pipe separated roles: role:system-admin|department-director
        $allowed = collect($roles)
            ->flatMap(fn($r) => preg_split('/[|,]/', $r))
            ->map(fn($r) => trim((string) $r))
            ->filter()
            ->values();

        if ($allowed->isEmpty()) {
            return $next($request);
        }

        if ($user->getRoleNames()->intersect($allowed)->isNotEmpty()) {
            return $next($request);
        }

        abort(403);
    }
}
