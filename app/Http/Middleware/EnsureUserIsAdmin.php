<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Blokuje całą grupę endpointów (np. /api/admin/*) dla osób bez roli admin -> 403.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            throw new AccessDeniedHttpException('Ta operacja wymaga uprawnień administratora.');
        }

        return $next($request);
    }
}
