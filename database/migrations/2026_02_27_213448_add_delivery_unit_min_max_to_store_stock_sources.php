<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_stock_sources', function (Blueprint $table) {
            if (!Schema::hasColumn('store_stock_sources', 'delivery_unit')) {
                $table->string('delivery_unit', 16)->nullable()->after('priority'); // days|hours|null
            }
            if (!Schema::hasColumn('store_stock_sources', 'delivery_min')) {
                $table->unsignedInteger('delivery_min')->nullable()->after('delivery_unit');
            }
            if (!Schema::hasColumn('store_stock_sources', 'delivery_max')) {
                $table->unsignedInteger('delivery_max')->nullable()->after('delivery_min');
            }
        });

        // backfill зі старих *_days
        if (Schema::hasColumn('store_stock_sources', 'min_delivery_days') && Schema::hasColumn('store_stock_sources', 'delivery_min')) {
            DB::statement("UPDATE store_stock_sources SET delivery_unit = COALESCE(delivery_unit, 'days')
                           WHERE min_delivery_days IS NOT NULL OR max_delivery_days IS NOT NULL");
            DB::statement("UPDATE store_stock_sources SET delivery_min = COALESCE(delivery_min, min_delivery_days)
                           WHERE min_delivery_days IS NOT NULL");
            DB::statement("UPDATE store_stock_sources SET delivery_max = COALESCE(delivery_max, max_delivery_days)
                           WHERE max_delivery_days IS NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('store_stock_sources', function (Blueprint $table) {
            if (Schema::hasColumn('store_stock_sources', 'delivery_max')) $table->dropColumn('delivery_max');
            if (Schema::hasColumn('store_stock_sources', 'delivery_min')) $table->dropColumn('delivery_min');
            if (Schema::hasColumn('store_stock_sources', 'delivery_unit')) $table->dropColumn('delivery_unit');
        });
    }
};