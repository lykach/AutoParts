<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('stock_source_id');
            $table->unsignedBigInteger('product_id');

            // залишок
            $table->decimal('qty', 12, 3)->default(0);

            // мін. замовлення (кількість)
            $table->unsignedInteger('min_order_qty')->nullable();

            // ціни
            $table->decimal('price_purchase', 12, 2)->nullable(); // закупка
            $table->decimal('price_sell', 12, 2)->nullable();     // продаж (можна кешувати, або рахувати правилом)
            $table->string('currency', 3)->default('UAH');

            // доставка/строки
            $table->unsignedSmallInteger('delivery_days_min')->nullable();
            $table->unsignedSmallInteger('delivery_days_max')->nullable();

            // дата оновлення з джерела (прайс/API)
            $table->timestamp('source_updated_at')->nullable();

            // службове
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['stock_source_id', 'product_id'], 'stock_items_source_product_unique');
            $table->index(['product_id', 'qty']);
            $table->index('stock_source_id');

            $table->foreign('stock_source_id')->references('id')->on('stock_sources')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
