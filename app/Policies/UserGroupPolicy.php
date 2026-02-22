<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserGroup;

class UserGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('user-groups.view');
    }

    public function view(User $user, UserGroup $group): bool
    {
        return $user->can('user-groups.view');
    }

    public function create(User $user): bool
    {
        return $user->can('user-groups.create');
    }

    public function update(User $user, UserGroup $group): bool
    {
        return $user->can('user-groups.update');
    }

    public function delete(User $user, UserGroup $group): bool
    {
        // додатковий захист: не видаляємо, якщо є користувачі
        if ($group->users()->exists()) {
            return false;
        }

        // супер-адмін може завжди
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('user-groups.delete');
    }
}
