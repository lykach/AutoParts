<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_delivery_methods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('delivery_method_id')
                ->constrained('delivery_methods')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(100);
            $table->json('settings')->nullable();

            $table->timestamps();

            $table->unique(
                ['store_id', 'delivery_method_id'],
                'ux_store_delivery_methods_store_delivery'
            );

            $table->index(['store_id', 'is_active']);
            $table->index(['delivery_method_id', 'is_active']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_delivery_methods');
    }
};