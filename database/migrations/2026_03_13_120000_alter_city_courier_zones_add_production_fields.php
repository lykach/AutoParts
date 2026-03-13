<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('city_courier_zones', function (Blueprint $table) {
            $table->boolean('cash_allowed')->default(true)->after('free_from_amount');
            $table->boolean('card_allowed')->default(true)->after('cash_allowed');
            $table->boolean('cod_allowed')->default(false)->after('card_allowed');

            $table->boolean('same_day_available')->default(true)->after('cod_allowed');
            $table->time('order_cutoff_at')->nullable()->after('same_day_available');

            $table->time('work_time_from')->nullable()->after('order_cutoff_at');
            $table->time('work_time_to')->nullable()->after('work_time_from');

            $table->json('work_days')->nullable()->after('work_time_to');

            $table->decimal('min_order_amount', 10, 2)->nullable()->after('work_days');
            $table->decimal('max_order_amount', 10, 2)->nullable()->after('min_order_amount');

            $table->decimal('weight_limit_kg', 10, 3)->nullable()->after('max_order_amount');

            $table->text('manager_note')->nullable()->after('weight_limit_kg');

            $table->index(['same_day_available', 'is_active'], 'idx_ccz_same_day_active');
            $table->index(['cash_allowed', 'card_allowed', 'cod_allowed'], 'idx_ccz_payments');
        });
    }

    public function down(): void
    {
        Schema::table('city_courier_zones', function (Blueprint $table) {
            $table->dropIndex('idx_ccz_same_day_active');
            $table->dropIndex('idx_ccz_payments');

            $table->dropColumn([
                'cash_allowed',
                'card_allowed',
                'cod_allowed',
                'same_day_available',
                'order_cutoff_at',
                'work_time_from',
                'work_time_to',
                'work_days',
                'min_order_amount',
                'max_order_amount',
                'weight_limit_kg',
                'manager_note',
            ]);
        });
    }
};