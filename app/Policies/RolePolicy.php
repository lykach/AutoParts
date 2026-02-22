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
        return $user->can('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        // тільки super-admin може чіпати super-admin роль
        if ($role->name === 'super-admin') {
            return $user->hasRole('super-admin');
        }

        // super-admin може завжди
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('roles.update');
    }

    public function delete(User $user, Role $role): bool
    {
        // super-admin роль не видаляємо ніколи
        if ($role->name === 'super-admin') {
            return false;
        }

        // якщо роль призначена комусь — не видаляємо
        if ($role->users()->exists()) {
            return false;
        }

        // супер-адмін може завжди
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('roles.delete');
    }
}
