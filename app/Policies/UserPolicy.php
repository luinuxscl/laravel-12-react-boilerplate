<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'root']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Solo un root puede ver perfiles de otros root
        if ($this->isRoot($model)) {
            return $user->hasRole('root');
        }

        return $user->id === $model->id || $user->hasRole(['admin', 'root']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Crear usuarios (no implica asignar rol root aquí)
        return $user->hasRole(['admin', 'root']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Solo un root puede actualizar a un usuario root
        if ($this->isRoot($model)) {
            return $user->hasRole('root');
        }

        return $user->hasRole(['admin', 'root']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Solo un root puede eliminar a un usuario root
        if ($this->isRoot($model)) {
            return $user->hasRole('root');
        }

        return $user->hasRole(['admin', 'root']);
    }

    protected function isRoot(User $user): bool
    {
        return $user->hasRole('root');
    }
}
