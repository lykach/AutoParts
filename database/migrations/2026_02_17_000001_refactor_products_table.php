<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Додаємо нові колонки в products
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'manufacturer_id')) {
                $table->unsignedBigInteger('manufacturer_id')->nullable()->after('category_id');
            }

            if (!Schema::hasColumn('products', 'article_raw')) {
                $table->string('article_raw', 128)->nullable()->after('manufacturer_id');
            }

            if (!Schema::hasColumn('products', 'article_norm')) {
                $table->string('article_norm', 128)->nullable()->after('article_raw');
                $table->index('article_norm');
            }

            if (!Schema::hasColumn('products', 'created_source')) {
                $table->string('created_source', 32)->default('manual')->after('is_active');
            }

            if (!Schema::hasColumn('products', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('created_source');
            }

            if (!Schema::hasColumn('products', 'tecdoc_id')) {
                $table->unsignedBigInteger('tecdoc_id')->nullable()->after('created_by');
                $table->index('tecdoc_id');
            }
        });

        // 2) Переносимо sku -> article_raw (якщо article_raw порожній)
        DB::statement("
            UPDATE products
            SET article_raw = COALESCE(article_raw, sku)
            WHERE article_raw IS NULL OR article_raw = ''
        ");

        // 3) Підчистимо article_norm базово в SQL (потім у моделі буде нормалізація при збереженні)
        //    Тут мінімально: прибрати пробіли, дефіси, крапки, зробити uppercase.
        DB::statement("
            UPDATE products
            SET article_norm = UPPER(
                REPLACE(REPLACE(REPLACE(COALESCE(article_raw, ''), ' ', ''), '-', ''), '.', '')
            )
            WHERE article_norm IS NULL OR article_norm = ''
        ");

        // 4) Індекси/унікальність: manufacturer_id може бути null -> MySQL дозволяє багато NULL в unique
        Schema::table('products', function (Blueprint $table) {
            // Унікальність по (manufacturer_id, article_norm) - якщо ще не існує
            // (назву індексу задаємо явно)
            $table->unique(['manufacturer_id', 'article_norm'], 'products_manufacturer_article_norm_unique');
        });

        // 5) Прибираємо старі текстові поля з products (вони підуть в product_translations)
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'name_uk')) $table->dropColumn('name_uk');
            if (Schema::hasColumn('products', 'name_en')) $table->dropColumn('name_en');
            if (Schema::hasColumn('products', 'name_ru')) $table->dropColumn('name_ru');

            // sku більше не потрібен в products (sku буде в JSON-LD як article_raw)
            if (Schema::hasColumn('products', 'sku')) $table->dropColumn('sku');
        });
    }

    public function down(): void
    {
        // Повернення назад робимо обережно (без відновлення даних перекладів).
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'name_uk')) $table->string('name_uk')->nullable();
            if (!Schema::hasColumn('products', 'name_en')) $table->string('name_en')->nullable();
            if (!Schema::hasColumn('products', 'name_ru')) $table->string('name_ru')->nullable();
            if (!Schema::hasColumn('products', 'sku')) $table->string('sku', 128)->nullable();

            if (Schema::hasColumn('products', 'tecdoc_id')) $table->dropColumn('tecdoc_id');
            if (Schema::hasColumn('products', 'created_by')) $table->dropColumn('created_by');
            if (Schema::hasColumn('products', 'created_source')) $table->dropColumn('created_source');

            if (Schema::hasColumn('products', 'article_norm')) $table->dropColumn('article_norm');
            if (Schema::hasColumn('products', 'article_raw')) $table->dropColumn('article_raw');

            // unique
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('products');
            if (isset($indexes['products_manufacturer_article_norm_unique'])) {
                $table->dropUnique('products_manufacturer_article_norm_unique');
            }

            if (Schema::hasColumn('products', 'manufacturer_id')) $table->dropColumn('manufacturer_id');
        });
    }
};
