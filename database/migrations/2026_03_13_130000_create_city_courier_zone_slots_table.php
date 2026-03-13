<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_courier_zone_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('city_courier_zone_id')
                ->constrained('city_courier_zones')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('name', 150)->nullable();

            $table->json('work_days')->nullable(); // mon..sun

            $table->time('delivery_time_from');
            $table->time('delivery_time_to');

            $table->boolean('same_day_enabled')->default(true);
            $table->time('same_day_cutoff_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(100);

            $table->text('manager_note')->nullable();
            $table->json('settings')->nullable();

            $table->timestamps();

            $table->index(['city_courier_zone_id', 'is_active'], 'idx_cczs_zone_active');
            $table->index(['is_active', 'sort_order'], 'idx_cczs_active_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_courier_zone_slots');
    }
};