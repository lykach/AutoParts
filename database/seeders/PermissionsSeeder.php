<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // USERS
            ['users.view', 'Перегляд користувачів'],
            ['users.create', 'Створення користувачів'],
            ['users.update', 'Редагування користувачів'],
            ['users.delete', 'Видалення користувачів'],

            // USER GROUPS
            ['user-groups.view', 'Перегляд груп користувачів'],
            ['user-groups.create', 'Створення груп користувачів'],
            ['user-groups.update', 'Редагування груп користувачів'],
            ['user-groups.delete', 'Видалення груп користувачів'],

            // ROLES
            ['roles.view', 'Перегляд ролей'],
            ['roles.create', 'Створення ролей'],
            ['roles.update', 'Редагування ролей'],
            ['roles.delete', 'Видалення ролей'],

            // PERMISSIONS
            ['permissions.view', 'Перегляд permissions'],
            ['permissions.create', 'Створення permissions'],
            ['permissions.update', 'Редагування permissions'],
            ['permissions.delete', 'Видалення permissions'],

            // CATEGORIES
            ['categories.view', 'Перегляд категорій'],
            ['categories.create', 'Створення категорій'],
            ['categories.update', 'Редагування категорій'],
            ['categories.delete', 'Видалення категорій'],

            // CATEGORY MIRRORS
            ['category-mirrors.view', 'Перегляд дублікатів категорій'],
            ['category-mirrors.create', 'Створення дублікатів категорій'],
            ['category-mirrors.update', 'Редагування дублікатів категорій'],
            ['category-mirrors.delete', 'Видалення дублікатів категорій'],
        ];

        foreach ($permissions as [$name, $description]) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }
    }
}
