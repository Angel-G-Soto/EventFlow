<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActiveRole
{
  public function handle(Request $request, Closure $next, ...$allowed)
  {
    $active = session('active_role');

    // If no active role yet, send user to picker to choose one
    if (!$active) {
      $user = Auth::user();
      if ($user) {
        return redirect()->route('role.select');
      }
    }

    // If this route demands specific roles, enforce them
    if (!empty($allowed) && $active && !in_array($active, $allowed, true)) {
      abort(403);
    }

    return $next($request);
  }
}
