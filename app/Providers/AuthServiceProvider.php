<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Laravel 12 soporta auto-discovery por convención: App\Models\User -> App\Policies\UserPolicy
        // Si necesitas mapear manualmente, descomenta:
        // User::class => \App\Policies\UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Gate: gestión general de roles (excluye rol 'root' salvo que el actor sea root)
        Gate::define('manage-roles', function (User $actor, ?User $target = null, ?array $roles = null) {
            $actorRoles = collect($actor->getRoleNames())->map(fn ($r) => strtolower($r));
            $isAdminOrRoot = $actorRoles->intersect(['admin', 'root'])->isNotEmpty();
            if (! $isAdminOrRoot) {
                return false;
            }

            $roles = $roles ? array_map('strtolower', $roles) : [];
            $assigningRoot = in_array('root', $roles, true);
            if ($assigningRoot && ! $actorRoles->contains('root')) {
                return false;
            }

            if ($target) {
                $targetIsRoot = collect($target->getRoleNames())->map(fn ($r) => strtolower($r))->contains('root');
                if ($targetIsRoot && ! $actorRoles->contains('root')) {
                    return false;
                }
            }

            return true;
        });

        // Gate explícito para asignar el rol root (case-insensitive)
        Gate::define('assign-root', function (User $actor) {
            return collect($actor->getRoleNames())->map(fn ($r) => strtolower($r))->contains('root');
        });
    }
}
