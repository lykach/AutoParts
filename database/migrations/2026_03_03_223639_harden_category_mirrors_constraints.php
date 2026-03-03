<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) На всяк випадок: прибираємо дублікати 1:1 (залишаємо найменший id)
        //    (перед UNIQUE інакше міграція впаде)
        DB::statement("
            DELETE cm1 FROM category_mirrors cm1
            INNER JOIN category_mirrors cm2
                ON cm1.parent_category_id = cm2.parent_category_id
               AND cm1.source_category_id = cm2.source_category_id
               AND cm1.id > cm2.id
        ");

        // 2) Додаємо індекси + UNIQUE
        Schema::table('category_mirrors', function (Blueprint $table) {
            // індекси для швидких JOIN/фільтрів
            $table->index('parent_category_id', 'cm_parent_idx');
            $table->index('source_category_id', 'cm_source_idx');

            // ✅ головне правило: одна source під одним parent тільки один раз
            $table->unique(['parent_category_id', 'source_category_id'], 'cm_parent_source_unique');
        });

        // 3) Foreign keys (RESTRICT)
        // Важливо: якщо у тебе вже існують FK з іншими іменами — ці drop можуть впасти.
        // Тому робимо "тихо": спробуємо дропнути типові імена, якщо вони були.
        try {
            Schema::table('category_mirrors', function (Blueprint $table) {
                $table->dropForeign(['parent_category_id']);
            });
        } catch (\Throwable $e) {
            // ігноруємо
        }

        try {
            Schema::table('category_mirrors', function (Blueprint $table) {
                $table->dropForeign(['source_category_id']);
            });
        } catch (\Throwable $e) {
            // ігноруємо
        }

        Schema::table('category_mirrors', function (Blueprint $table) {
            $table->foreign('parent_category_id', 'cm_parent_fk')
                ->references('id')->on('categories')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('source_category_id', 'cm_source_fk')
                ->references('id')->on('categories')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        // Відкат: прибираємо FK, UNIQUE, індекси
        try {
            Schema::table('category_mirrors', function (Blueprint $table) {
                $table->dropForeign('cm_parent_fk');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('category_mirrors', function (Blueprint $table) {
                $table->dropForeign('cm_source_fk');
            });
        } catch (\Throwable $e) {}

        Schema::table('category_mirrors', function (Blueprint $table) {
            try { $table->dropUnique('cm_parent_source_unique'); } catch (\Throwable $e) {}
            try { $table->dropIndex('cm_parent_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('cm_source_idx'); } catch (\Throwable $e) {}
        });
    }
};