<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {

            // image_path -> nullable (бо буде source=url)
            $table->string('image_path', 512)->nullable()->change();

            // URL зображення (CDN / інші сервери)
            if (!Schema::hasColumn('product_images', 'external_url')) {
                $table->string('external_url', 1024)->nullable()->after('image_path');
            }

            // source: upload | url
            if (!Schema::hasColumn('product_images', 'source')) {
                $table->string('source', 20)
                    ->default('upload')
                    ->after('product_id');
            }
        });

        // optional: нормалізація старих даних
        DB::table('product_images')
            ->whereNull('source')
            ->update(['source' => 'upload']);
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {

            if (Schema::hasColumn('product_images', 'external_url')) {
                $table->dropColumn('external_url');
            }

            if (Schema::hasColumn('product_images', 'source')) {
                $table->dropColumn('source');
            }

            // повернути назад NOT NULL (якщо потрібно)
            $table->string('image_path', 512)->nullable(false)->change();
        });
    }
};
