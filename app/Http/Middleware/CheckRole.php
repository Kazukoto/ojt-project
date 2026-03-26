<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     * Usage: Route::middleware('role:admin,timekeeper')->group(...)
     */
    public function handle(Request $request, Closure $next, ...$roles)
{
    if (!Auth::check()) {
        return redirect('/login');
    }

    $userRole = Auth::user()->role?->role_name;

    foreach ($roles as $role) {
        if (strtolower($userRole) === strtolower($role)) {
            return $next($request);
        }
    }

    abort(403);
}


}