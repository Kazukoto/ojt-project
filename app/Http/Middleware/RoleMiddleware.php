<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$roles)
{
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    if (!in_array(auth()->user()->role_id, $roles)) {
        abort(403, 'Unauthorized Access');
    }

    return $next($request);
}

}
