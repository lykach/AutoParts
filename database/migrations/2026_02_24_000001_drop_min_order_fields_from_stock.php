<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * Якщо раніше хтось руками задавав min_order_qty, а pack_qty лишав 1 —
         * то логічно перенести min_order_qty -> pack_qty (тільки коли pack_qty <= 1).
         *
         * Це безпечно: не чіпаємо pack_qty, якщо він вже > 1.
         */
        if (Schema::hasColumn('stock_items', 'min_order_qty')) {
            DB::table('stock_items')
                ->whereNotNull('min_order_qty')
                ->where(function ($q) {
                    $q->whereNull('pack_qty')->orWhere('pack_qty', '<=', 1);
                })
                ->update([
                    'pack_qty' => DB::raw('min_order_qty'),
                ]);
        }

        Schema::table('stock_sources', function (Blueprint $table) {
            if (Schema::hasColumn('stock_sources', 'min_order_default_qty')) {
                $table->dropColumn('min_order_default_qty');
            }
        });

        Schema::table('stock_items', function (Blueprint $table) {
            if (Schema::hasColumn('stock_items', 'min_order_qty')) {
                $table->dropColumn('min_order_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_sources', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_sources', 'min_order_default_qty')) {
                $table->unsignedInteger('min_order_default_qty')->nullable()->after('sort_order');
            }
        });

        Schema::table('stock_items', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_items', 'min_order_qty')) {
                $table->unsignedInteger('min_order_qty')->nullable()->after('availability_status');
            }
        });
    }
};