<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stock_summary', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->primary();

            $table->boolean('is_in_stock')->default(false);
            $table->decimal('total_sellable_qty', 12, 3)->default(0);

            $table->decimal('min_price_uah', 12, 2)->nullable();
            $table->decimal('max_price_uah', 12, 2)->nullable();

            $table->unsignedBigInteger('best_stock_item_id')->nullable();
            $table->unsignedBigInteger('best_stock_source_id')->nullable();
            $table->unsignedBigInteger('best_stock_source_location_id')->nullable();

            $table->smallInteger('delivery_days_min')->nullable();
            $table->smallInteger('delivery_days_max')->nullable();

            $table->dateTime('updated_at')->useCurrent();

            $table->index(['is_in_stock']);
            $table->index(['min_price_uah']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stock_summary');
    }
};