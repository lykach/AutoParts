<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_courier_slot_exceptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('city_courier_zone_slot_id')
                ->constrained('city_courier_zone_slots')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->date('exception_date');

            $table->boolean('is_closed')->default(false);

            $table->time('override_delivery_time_from')->nullable();
            $table->time('override_delivery_time_to')->nullable();
            $table->time('override_cutoff_at')->nullable();

            $table->decimal('override_price', 10, 2)->nullable();
            $table->unsignedInteger('override_eta_min_minutes')->nullable();
            $table->unsignedInteger('override_eta_max_minutes')->nullable();

            $table->unsignedInteger('max_orders')->nullable();

            $table->text('manager_note')->nullable();
            $table->json('settings')->nullable();

            $table->timestamps();

            $table->unique(
                ['city_courier_zone_slot_id', 'exception_date'],
                'ux_ccse_slot_date'
            );

            $table->index(
                ['exception_date', 'is_closed'],
                'idx_ccse_date_closed'
            );

            $table->index(
                ['city_courier_zone_slot_id', 'exception_date'],
                'idx_ccse_slot_date'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_courier_slot_exceptions');
    }
};