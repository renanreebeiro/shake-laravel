<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAreaAccess
{
    public function handle(Request $request, Closure $next, string $area): Response
    {
        abort_unless($request->user()->hasAreaAccess($area), 403);

        return $next($request);
    }
}
