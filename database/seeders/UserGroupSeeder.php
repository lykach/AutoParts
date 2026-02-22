<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $groups = [
            [
                'name' => 'Роздрібний клієнт',
                'discount_percent' => 0,
                'markup_percent' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Оптовий клієнт',
                'discount_percent' => 10,
                'markup_percent' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'СТО (Сервіс)',
                'discount_percent' => 0,
                'markup_percent' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Автосалон',
                'discount_percent' => 0,
                'markup_percent' => 8,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'VIP клієнт',
                'discount_percent' => 15,
                'markup_percent' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Партнер',
                'discount_percent' => 12,
                'markup_percent' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Дилер',
                'discount_percent' => 20,
                'markup_percent' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Корпоративний клієнт',
                'discount_percent' => 8,
                'markup_percent' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Автопарк',
                'discount_percent' => 0,
                'markup_percent' => 7,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Майстерня',
                'discount_percent' => 0,
                'markup_percent' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // ✅ Видаляємо тільки якщо немає прив'язаних користувачів
        DB::table('user_groups')
            ->whereNotIn('id', function($query) {
                $query->select('user_group_id')
                    ->from('users')
                    ->whereNotNull('user_group_id');
            })
            ->delete();

        // Вставляємо нові групи (або оновлюємо якщо вже існують)
        foreach ($groups as $group) {
            DB::table('user_groups')->updateOrInsert(
                ['name' => $group['name']],
                $group
            );
        }

        $this->command->info('✅ Створено/оновлено ' . count($groups) . ' груп користувачів');
    }
}