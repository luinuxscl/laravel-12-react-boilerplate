<?php

namespace App\Http\Middleware;

use App\Services\TenantResolver;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SetTenant
{
    public function __construct(
        protected TenantResolver $resolver,
        protected TenantContext $context
    ) {
    }

    /**
     * Maneja la resolución de tenant antes del resto del stack.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('tenancy.enabled', true)) {
            return $next($request);
        }

        // Ignorar ciertas rutas (auth, health, assets) definidas en config
        $path = '/'.ltrim($request->path(), '/');
        foreach (config('tenancy.ignore_paths', []) as $pattern) {
            if (Str::is($pattern, $path)) {
                return $next($request);
            }
        }

        $tenant = $this->resolver->resolve($request);
        if (! $tenant) {
            // Si hay subdominio u host no resoluble, devolvemos 404
            abort(404, 'Tenant could not be resolved');
        }

        $this->context->set($tenant);

        return $next($request);
    }
}
