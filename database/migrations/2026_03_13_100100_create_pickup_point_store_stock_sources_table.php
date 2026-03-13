<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_point_store_stock_sources', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pickup_point_id')
                ->constrained('delivery_pickup_points')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('store_stock_source_id')
                ->constrained('store_stock_sources')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(100);

            $table->string('transfer_time_unit', 20)->default('hour'); // minute | hour | day
            $table->unsignedInteger('transfer_time_min')->default(0);
            $table->unsignedInteger('transfer_time_max')->default(0);

            $table->time('cutoff_at')->nullable();

            $table->text('note')->nullable();
            $table->json('settings')->nullable();

            $table->timestamps();

            $table->unique(
                ['pickup_point_id', 'store_stock_source_id'],
                'ux_ppsss_point_source'
            );

            $table->index(
                ['pickup_point_id', 'is_active'],
                'idx_ppsss_point_active'
            );

            $table->index(
                ['store_stock_source_id', 'is_active'],
                'idx_ppsss_source_active'
            );

            $table->index(
                ['priority', 'is_active'],
                'idx_ppsss_priority_active'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_point_store_stock_sources');
    }
};