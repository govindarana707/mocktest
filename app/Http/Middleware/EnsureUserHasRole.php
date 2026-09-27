<?php

namespace App\Http\Middleware;

use App\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $expectedRole = UserRole::tryFrom($role);

        abort_unless($expectedRole !== null && $request->user()?->role === $expectedRole, 403);

        return $next($request);
    }
}
