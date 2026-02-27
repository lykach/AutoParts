<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // stock_sources
        Schema::table('stock_sources', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_sources', 'delivery_unit')) {
                $table->string('delivery_unit', 16)->default('days')->after('default_currency_code'); // days|hours
            }
            if (! Schema::hasColumn('stock_sources', 'delivery_min')) {
                $table->unsignedInteger('delivery_min')->nullable()->after('delivery_unit');
            }
            if (! Schema::hasColumn('stock_sources', 'delivery_max')) {
                $table->unsignedInteger('delivery_max')->nullable()->after('delivery_min');
            }
        });

        // stock_source_locations
        Schema::table('stock_source_locations', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_source_locations', 'delivery_unit')) {
                $table->string('delivery_unit', 16)->nullable()->after('sort_order'); // null = inherit
            }
            if (! Schema::hasColumn('stock_source_locations', 'delivery_min')) {
                $table->unsignedInteger('delivery_min')->nullable()->after('delivery_unit');
            }
            if (! Schema::hasColumn('stock_source_locations', 'delivery_max')) {
                $table->unsignedInteger('delivery_max')->nullable()->after('delivery_min');
            }
        });

        // stock_items
        Schema::table('stock_items', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_items', 'delivery_unit')) {
                $table->string('delivery_unit', 16)->nullable()->after('currency'); // null = inherit
            }
            if (! Schema::hasColumn('stock_items', 'delivery_min')) {
                $table->unsignedInteger('delivery_min')->nullable()->after('delivery_unit');
            }
            if (! Schema::hasColumn('stock_items', 'delivery_max')) {
                $table->unsignedInteger('delivery_max')->nullable()->after('delivery_min');
            }
        });

        // ✅ Backfill: старі delivery_days_* -> нові delivery_* як days
        if (Schema::hasColumn('stock_items', 'delivery_days_min') && Schema::hasColumn('stock_items', 'delivery_min')) {
            DB::statement("UPDATE stock_items SET delivery_unit = COALESCE(delivery_unit,'days') WHERE delivery_days_min IS NOT NULL OR delivery_days_max IS NOT NULL");
            DB::statement("UPDATE stock_items SET delivery_min = COALESCE(delivery_min, delivery_days_min) WHERE delivery_days_min IS NOT NULL");
            DB::statement("UPDATE stock_items SET delivery_max = COALESCE(delivery_max, delivery_days_max) WHERE delivery_days_max IS NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            if (Schema::hasColumn('stock_items', 'delivery_max')) $table->dropColumn('delivery_max');
            if (Schema::hasColumn('stock_items', 'delivery_min')) $table->dropColumn('delivery_min');
            if (Schema::hasColumn('stock_items', 'delivery_unit')) $table->dropColumn('delivery_unit');
        });

        Schema::table('stock_source_locations', function (Blueprint $table) {
            if (Schema::hasColumn('stock_source_locations', 'delivery_max')) $table->dropColumn('delivery_max');
            if (Schema::hasColumn('stock_source_locations', 'delivery_min')) $table->dropColumn('delivery_min');
            if (Schema::hasColumn('stock_source_locations', 'delivery_unit')) $table->dropColumn('delivery_unit');
        });

        Schema::table('stock_sources', function (Blueprint $table) {
            if (Schema::hasColumn('stock_sources', 'delivery_max')) $table->dropColumn('delivery_max');
            if (Schema::hasColumn('stock_sources', 'delivery_min')) $table->dropColumn('delivery_min');
            if (Schema::hasColumn('stock_sources', 'delivery_unit')) $table->dropColumn('delivery_unit');
        });
    }
};