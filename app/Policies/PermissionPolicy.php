<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        // Найбезпечніше: permissions бачить тільки super-admin
        return $user->hasRole('super-admin') || $user->can('permissions.view');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->hasRole('super-admin') || $user->can('permissions.view');
    }

    public function create(User $user): bool
    {
        // Створення permissions — тільки super-admin (або permissions.create якщо захочеш)
        return $user->hasRole('super-admin') || $user->can('permissions.create');
    }

    public function update(User $user, Permission $permission): bool
    {
        // Редагування permissions — тільки super-admin (або permissions.update якщо захочеш)
        return $user->hasRole('super-admin') || $user->can('permissions.update');
    }

    public function delete(User $user, Permission $permission): bool
    {
        // Видалення permissions — тільки super-admin (або permissions.delete якщо захочеш)
        return $user->hasRole('super-admin') || $user->can('permissions.delete');
    }
}
