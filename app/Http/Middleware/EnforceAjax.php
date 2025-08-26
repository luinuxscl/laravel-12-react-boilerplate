<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceAjax
{
    /**
     * Ensure mutating requests are made via AJAX (X-Requested-With header).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            // Permitir solicitudes JSON (tests/API) sin exigir X-Requested-With
            if ($request->expectsJson() || $request->isJson()) {
                return $next($request);
            }

            $xr = $request->headers->get('X-Requested-With');
            if ($xr !== 'XMLHttpRequest') {
                abort(403, 'Forbidden: X-Requested-With header required');
            }
        }

        return $next($request);
    }
}
