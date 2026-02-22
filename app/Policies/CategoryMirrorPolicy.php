<?php

namespace App\Policies;

use App\Models\CategoryMirror;
use App\Models\User;

class CategoryMirrorPolicy
{
    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function viewAny(User $user): bool
    {
        return $this->isSuperAdmin($user) || $user->can('category-mirrors.view');
    }

    public function view(User $user, CategoryMirror $mirror): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isSuperAdmin($user) || $user->can('category-mirrors.create');
    }

    public function update(User $user, CategoryMirror $mirror): bool
    {
        return $this->isSuperAdmin($user) || $user->can('category-mirrors.update');
    }

    public function delete(User $user, CategoryMirror $mirror): bool
    {
        return $this->isSuperAdmin($user) || $user->can('category-mirrors.delete');
    }
}
