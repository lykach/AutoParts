<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('store_stock_sources', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('stock_source_id');

            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('priority')->default(100)->index(); // smaller = higher priority

            // logistics
            $table->unsignedSmallInteger('lead_time_days')->nullable(); // delivery lead time
            $table->string('cutoff_time', 5)->nullable(); // HH:MM, if order before -> same day processing
            $table->boolean('pickup_available')->default(false);

            // pricing rules
            $table->decimal('price_multiplier', 8, 4)->nullable(); // e.g. 1.0500
            $table->decimal('extra_fee', 10, 2)->nullable(); // fixed add-on
            $table->decimal('min_order_amount', 10, 2)->nullable();

            // coverage / misc
            $table->json('coverage')->nullable(); // e.g. regions/cities
            $table->json('settings')->nullable();
            $table->string('note', 255)->nullable();

            $table->timestamps();

            $table->unique(['store_id', 'stock_source_id']);
            $table->index(['store_id', 'priority']);
            $table->index(['stock_source_id']);

            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
            $table->foreign('stock_source_id')->references('id')->on('stock_sources')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_stock_sources');
    }
};
