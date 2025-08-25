<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        // La validación de crear 'root' se maneja en request/controller.
        return $user->can('roles.manage');
    }

    public function update(User $user, Role $role): bool
    {
        if ($role->name === 'root') {
            return $user->can('roles.manage_root');
        }
        return $user->can('roles.manage');
    }

    public function delete(User $user, Role $role): bool
    {
        if ($role->name === 'root') {
            return $user->can('roles.manage_root');
        }
        return $user->can('roles.manage');
    }
}
