<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\CategoryMirror;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\StockItem;                 // ✅ NEW
use App\Observers\StockItemObserver;      // ✅ NEW
use App\Policies\CategoryMirrorPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserGroupPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /**
         * ✅ Global bypass для super-admin
         * Якщо користувач має роль super-admin — дозволяємо ВСЕ.
         * Для інших — працюють звичайні Policies/permissions.
         */
        Gate::before(function (?User $user, string $ability) {
            if (! $user) {
                return null;
            }

            return $user->hasRole('super-admin') ? true : null;
        });

        /*
        |--------------------------------------------------------------------------
        | Register Policies
        |--------------------------------------------------------------------------
        */

        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(UserGroup::class, UserGroupPolicy::class);

        // ✅ Каталог
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(CategoryMirror::class, CategoryMirrorPolicy::class);

        // ✅ NEW: автоматичний перерахунок best offer при зміні stock_items
        StockItem::observe(StockItemObserver::class);
    }
}
