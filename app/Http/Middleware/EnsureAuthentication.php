<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
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
            // Redirect to the saml.login route
            session()->put('url.intended', $request->fullUrl() ?: '/');
//            dd(session('url.intended'));
            return Socialite::driver('saml2')->redirect();
        }

        // Proceed with the request if authenticated
        return $next($request);
    }
}
