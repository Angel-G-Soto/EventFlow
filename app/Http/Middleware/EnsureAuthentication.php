<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is not authenticated
        if (!Auth::check()) {
            // Remember where the user was headed so we can send them back after SAML login
            if ($request->isMethod('GET')) {
                $intended = $request->fullUrl() ?: '/';

                session()->put('url.intended', $intended);

                Cookie::queue(cookie(
                    'saml_intended',
                    $intended,
                    10, // minutes
                    '/',
                    config('session.domain'),
                    $request->isSecure(),
                    true,
                    false,
                    'none'
                ));
            }

            return redirect()->route('saml.login');
        }

        // Proceed with the request if authenticated
        return $next($request);
    }
}
