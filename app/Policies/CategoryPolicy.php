<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function viewAny(User $user): bool
    {
        return $this->isSuperAdmin($user) || $user->can('categories.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isSuperAdmin($user) || $user->can('categories.create');
    }

    public function update(User $user, Category $category): bool
    {
        return $this->isSuperAdmin($user) || $user->can('categories.update');
    }

    public function delete(User $user, Category $category): bool
    {
        // Спочатку — права
        if (!($this->isSuperAdmin($user) || $user->can('categories.delete'))) {
            return false;
        }

        // Safe delete: не можна видаляти якщо є підкатегорії
        if ($category->children()->exists()) {
            return false;
        }

        // Safe delete: не можна видаляти якщо є товари
        if ($category->hasProducts()) {
            return false;
        }

        return true;
    }
}
