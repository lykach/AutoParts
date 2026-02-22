<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignRolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::where('name', 'super-admin')->first();
        $admin = Role::where('name', 'admin')->first();
        $manager = Role::where('name', 'manager')->first();

        $allPermissions = Permission::where('guard_name', 'web')->get();

        // SUPER ADMIN — ВСЕ
        $superAdmin?->syncPermissions($allPermissions);

        // ADMIN — майже все (без delete roles/permissions)
        $adminPermissions = Permission::whereIn('name', [
            'users.view','users.create','users.update','users.delete',
            'user-groups.view','user-groups.create','user-groups.update',
            'roles.view','roles.create','roles.update',
            'permissions.view',
            'categories.view','categories.create','categories.update','categories.delete',
            'category-mirrors.view','category-mirrors.create','category-mirrors.update','category-mirrors.delete',
        ])->get();

        $admin?->syncPermissions($adminPermissions);

        // MANAGER — тільки перегляд + базове
        $managerPermissions = Permission::whereIn('name', [
            'users.view',
            'user-groups.view',
            'roles.view',
            'categories.view',
            'category-mirrors.view',
        ])->get();

        $manager?->syncPermissions($managerPermissions);
    }
}
