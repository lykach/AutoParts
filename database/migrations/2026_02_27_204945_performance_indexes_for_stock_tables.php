<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            // ✅ базові індекси (часто використовуються у фільтрах/джойнах)
            $table->index('product_id', 'stock_items_product_id_idx');
            $table->index('stock_source_id', 'stock_items_stock_source_id_idx');
            $table->index('stock_source_location_id', 'stock_items_stock_source_location_id_idx');

            // ✅ композитний індекс під найчастіші запити: товар → склад
            // (ідеально під upsert/оновлення прайсів та витяг по товарах)
            $table->index(
                ['product_id', 'stock_source_location_id'],
                'stock_items_product_location_idx'
            );

            // ✅ якщо в тебе 1 запис на (product_id + location) — ставимо UNIQUE
            $table->unique(
                ['product_id', 'stock_source_location_id'],
                'stock_items_product_location_unique'
            );

            // (опційно) якщо часто фільтруєш по статусу в наявності:
            // $table->index(['product_id', 'availability_status'], 'stock_items_product_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            // ВАЖЛИВО: спочатку drop unique, потім індекси
            $table->dropUnique('stock_items_product_location_unique');

            $table->dropIndex('stock_items_product_location_idx');
            $table->dropIndex('stock_items_product_id_idx');
            $table->dropIndex('stock_items_stock_source_id_idx');
            $table->dropIndex('stock_items_stock_source_location_id_idx');

            // $table->dropIndex('stock_items_product_status_idx');
        });
    }
};