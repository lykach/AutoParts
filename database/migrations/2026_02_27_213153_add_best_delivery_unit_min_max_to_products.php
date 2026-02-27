<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'best_delivery_unit')) {
                $table->string('best_delivery_unit', 16)->nullable()->after('best_stock_qty'); // days|hours
            }
            if (!Schema::hasColumn('products', 'best_delivery_min')) {
                $table->unsignedInteger('best_delivery_min')->nullable()->after('best_delivery_unit');
            }
            if (!Schema::hasColumn('products', 'best_delivery_max')) {
                $table->unsignedInteger('best_delivery_max')->nullable()->after('best_delivery_min');
            }
        });

        // backfill зі старих best_delivery_days_* якщо вони є
        if (Schema::hasColumn('products', 'best_delivery_days_min') && Schema::hasColumn('products', 'best_delivery_min')) {
            DB::statement("UPDATE products SET best_delivery_unit = COALESCE(best_delivery_unit, 'days')
                           WHERE best_delivery_days_min IS NOT NULL OR best_delivery_days_max IS NOT NULL");
            DB::statement("UPDATE products SET best_delivery_min = COALESCE(best_delivery_min, best_delivery_days_min)
                           WHERE best_delivery_days_min IS NOT NULL");
            DB::statement("UPDATE products SET best_delivery_max = COALESCE(best_delivery_max, best_delivery_days_max)
                           WHERE best_delivery_days_max IS NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'best_delivery_max')) $table->dropColumn('best_delivery_max');
            if (Schema::hasColumn('products', 'best_delivery_min')) $table->dropColumn('best_delivery_min');
            if (Schema::hasColumn('products', 'best_delivery_unit')) $table->dropColumn('best_delivery_unit');
        });
    }
};