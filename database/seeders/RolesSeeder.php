<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Очистка кешу permission
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [
            [
                'name' => 'super-admin',
                'description' => 'Супер Адміністратор (повний доступ)',
            ],
            [
                'name' => 'admin',
                'description' => 'Адміністратор',
            ],
            [
                'name' => 'manager',
                'description' => 'Менеджер',
            ],
            [
                'name' => 'user',
                'description' => 'Користувач',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                [
                    'name' => $role['name'],
                    'guard_name' => 'web',
                ],
                [
                    'description' => $role['description'],
                ]
            );
        }

        // 🔥 Автоматично призначити super-admin першому користувачу
        $firstUser = User::find(1);

        if ($firstUser && ! $firstUser->hasRole('super-admin')) {
            $firstUser->assignRole('super-admin');
        }
    }
}
