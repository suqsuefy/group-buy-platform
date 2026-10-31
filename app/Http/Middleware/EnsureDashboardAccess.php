<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDashboardAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        abort_unless(
            $user && $user->status === 'active' && in_array($user->role, ['admin', 'vendor'], true),
            403
        );

        return $next($request);
    }
}
