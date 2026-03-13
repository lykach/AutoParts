<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('city_courier_zones', function (Blueprint $table) {
            $table->dropIndex('idx_ccz_same_day_active');

            $table->dropColumn([
                'same_day_available',
                'order_cutoff_at',
                'work_time_from',
                'work_time_to',
                'work_days',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('city_courier_zones', function (Blueprint $table) {
            $table->boolean('same_day_available')->default(true)->after('cod_allowed');
            $table->time('order_cutoff_at')->nullable()->after('same_day_available');
            $table->time('work_time_from')->nullable()->after('order_cutoff_at');
            $table->time('work_time_to')->nullable()->after('work_time_from');
            $table->json('work_days')->nullable()->after('work_time_to');

            $table->index(['same_day_available', 'is_active'], 'idx_ccz_same_day_active');
        });
    }
};