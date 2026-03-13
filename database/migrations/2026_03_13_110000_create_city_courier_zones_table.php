<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_courier_zones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('code', 64)->unique();

            $table->string('name_uk', 150);
            $table->string('name_en', 150)->nullable();
            $table->string('name_ru', 150)->nullable();

            $table->string('city_uk', 150);
            $table->string('city_en', 150)->nullable();
            $table->string('city_ru', 150)->nullable();

            $table->text('description_uk')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ru')->nullable();

            $table->decimal('delivery_price', 10, 2)->default(0);
            $table->decimal('free_from_amount', 10, 2)->nullable();

            $table->unsignedInteger('eta_min_minutes')->default(60);
            $table->unsignedInteger('eta_max_minutes')->default(180);

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(100);

            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'is_active'], 'idx_ccz_store_active');
            $table->index(['is_active', 'sort_order'], 'idx_ccz_active_sort');
            $table->index(['city_uk'], 'idx_ccz_city_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_courier_zones');
    }
};