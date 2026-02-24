<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            // ✅ додаємо локацію
            if (!Schema::hasColumn('stock_items', 'stock_source_location_id')) {
                $table->unsignedBigInteger('stock_source_location_id')->nullable()->after('stock_source_id');
                $table->index(['stock_source_location_id', 'product_id']);
            }

            // ✅ pack_qty -> multiplicity
            if (Schema::hasColumn('stock_items', 'pack_qty') && !Schema::hasColumn('stock_items', 'multiplicity')) {
                $table->renameColumn('pack_qty', 'multiplicity');
            }

            // ✅ прибираємо min_order_qty
            if (Schema::hasColumn('stock_items', 'min_order_qty')) {
                $table->dropColumn('min_order_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            if (Schema::hasColumn('stock_items', 'stock_source_location_id')) {
                $table->dropColumn('stock_source_location_id');
            }

            if (Schema::hasColumn('stock_items', 'multiplicity') && !Schema::hasColumn('stock_items', 'pack_qty')) {
                $table->renameColumn('multiplicity', 'pack_qty');
            }

            if (!Schema::hasColumn('stock_items', 'min_order_qty')) {
                $table->integer('min_order_qty')->nullable();
            }
        });
    }
};