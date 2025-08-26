<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantResolver
{
    public function resolve(Request $request): ?Tenant
    {
        if (!config('tenancy.enabled', true)) {
            return null;
        }

        // 1) Dev header support
        $useHeader = app()->environment('local') || config('tenancy.allow_header', false);
        if ($useHeader && $request->hasHeader('X-Tenant')) {
            $slug = (string) $request->header('X-Tenant');
            $tenant = Tenant::query()->where('slug', $slug)->first();
            if ($tenant) {
                return $tenant;
            }
        }

        // 2) Subdomain-based resolution: {slug}.domain.tld
        $host = (string) $request->getHost();
        // If domain is registered on tenant, match by exact domain first
        $byDomain = Tenant::query()->where('domain', $host)->first();
        if ($byDomain) {
            return $byDomain;
        }

        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            $subdomain = $parts[0];
            $tenant = Tenant::query()->where('slug', $subdomain)->first();
            if ($tenant) {
                return $tenant;
            }
            // If there is a subdomain but not found, unresolved
            return null;
        }

        // 3) Authenticated user's tenant fallback (useful on dev/root host)
        if (Auth::check()) {
            $user = Auth::user();
            $userTenantId = $user?->tenant_id;
            if ($userTenantId) {
                $byUser = Tenant::query()->find($userTenantId);
                if ($byUser) {
                    return $byUser;
                }
            }
        }

        // 4) Root host fallback -> default tenant (if any)
        return Tenant::query()->where('is_default', true)->first();
    }
}
