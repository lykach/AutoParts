<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        // супер-адмін може все (навіть якщо permission випадково не призначили)
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // ніхто (крім super-admin) не редагує super-admin користувачів
        if ($model->hasRole('super-admin')) {
            return false;
        }

        return $user->can('users.update');
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false; // не даємо видалити себе
        }

        if ($model->hasRole('super-admin')) {
            return false; // ніхто (крім super-admin) не видаляє super-admin
        }

        // супер-адмін може все
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('users.delete');
    }
}
