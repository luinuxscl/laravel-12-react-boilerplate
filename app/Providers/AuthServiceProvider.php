<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role as SpatieRole;

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
        SpatieRole::class => \App\Policies\RolePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Root tiene acceso a todo (permite todos los abilities/policies)
        Gate::before(function (User $user, string $ability) {
            $hasRoot = collect($user->getRoleNames())->map(fn ($r) => strtolower($r))->contains('root');
            return $hasRoot ? true : null; // null => continuar con checks normales
        });

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
