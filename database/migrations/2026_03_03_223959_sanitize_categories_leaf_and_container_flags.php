<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // 1) Контейнер не може бути leaf
            DB::table('categories')
                ->where('is_container', 1)
                ->where('is_leaf', 1)
                ->update(['is_leaf' => 0]);

            // 2) Якщо є діти — це не leaf
            // (ставимо is_leaf=0 всім, хто є батьком хоч однієї категорії)
            DB::statement("
                UPDATE categories c
                SET c.is_leaf = 0
                WHERE EXISTS (
                    SELECT 1
                    FROM categories ch
                    WHERE ch.parent_id = c.id
                )
            ");

            // 3) Якщо немає дітей і не контейнер — це leaf
            DB::statement("
                UPDATE categories c
                SET c.is_leaf = 1
                WHERE c.is_container = 0
                  AND NOT EXISTS (
                    SELECT 1
                    FROM categories ch
                    WHERE ch.parent_id = c.id
                  )
            ");
        });
    }

    public function down(): void
    {
        // Відкат робити не будемо (бо це санітарка даних).
        // Можна лишити порожнім.
    }
};