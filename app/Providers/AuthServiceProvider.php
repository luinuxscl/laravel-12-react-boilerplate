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
        Gate::define('manage-roles', function (User $actor, ?User $target = null, array $roles = []) {
            // admin o root pueden gestionar roles en general
            if (! $actor->hasRole(['admin', 'root'])) {
                return false;
            }

            // Si se intenta asignar 'root', solo permitido para actor root
            $assigningRoot = in_array('root', $roles, true);
            if ($assigningRoot && ! $actor->hasRole('root')) {
                return false;
            }

            // Evitar que alguien que no sea root modifique a un usuario root
            if ($target && $target->hasRole('root') && ! $actor->hasRole('root')) {
                return false;
            }

            return true;
        });

        // Gate explícito para asignar el rol root
        Gate::define('assign-root', fn (User $actor) => $actor->hasRole('root'));
    }
}
