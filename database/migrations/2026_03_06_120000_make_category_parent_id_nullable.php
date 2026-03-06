<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Спочатку дозволяємо NULL, але НЕ чіпаємо signed/unsigned
        DB::statement("
            ALTER TABLE categories
            MODIFY parent_id BIGINT NULL
        ");

        // 2. Потім переводимо старі root-записи з -1 у NULL
        DB::statement("
            UPDATE categories
            SET parent_id = NULL
            WHERE parent_id = -1
        ");

        // 3. Індекс для швидкого сортування в межах батька
        try {
            DB::statement("
                CREATE INDEX categories_parent_order_index
                ON categories (parent_id, `order`)
            ");
        } catch (\Throwable $e) {
            // індекс вже може існувати
        }
    }

    public function down(): void
    {
        // 1. Повертаємо NULL назад у -1
        DB::statement("
            UPDATE categories
            SET parent_id = -1
            WHERE parent_id IS NULL
        ");

        // 2. Знову робимо NOT NULL
        DB::statement("
            ALTER TABLE categories
            MODIFY parent_id BIGINT NOT NULL
        ");

        // 3. Пробуємо прибрати індекс
        try {
            DB::statement("
                DROP INDEX categories_parent_order_index ON categories
            ");
        } catch (\Throwable $e) {
            // якщо індексу нема — ігноруємо
        }
    }
};