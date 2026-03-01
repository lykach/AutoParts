<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_stock_sources', function (Blueprint $table) {
            // 1) new location id
            if (! Schema::hasColumn('store_stock_sources', 'stock_source_location_id')) {
                $table->unsignedBigInteger('stock_source_location_id')
                    ->nullable()
                    ->after('stock_source_id');

                $table->index(['store_id', 'stock_source_location_id'], 'sss_store_location_idx');
            }

            // 2) make sure delivery columns exist (they already exist in your dump)
            // delivery_unit, delivery_min, delivery_max are already present in your table

            // 3) remove pricing / extra columns (we move pricing into separate module)
            $drop = [
                'markup_percent',
                'lead_time_days',
                'min_delivery_days',
                'max_delivery_days',
                'cutoff_time',
                'pickup_available',
                'price_multiplier',
                'extra_fee',
                'min_order_amount',
                'coverage',
            ];

            foreach ($drop as $col) {
                if (Schema::hasColumn('store_stock_sources', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // constraints & unique (separate call so column exists)
        Schema::table('store_stock_sources', function (Blueprint $table) {
            // FK to locations
            $table->foreign('stock_source_location_id', 'sss_location_fk')
                ->references('id')
                ->on('stock_source_locations')
                ->nullOnDelete();

            // Unique: 1 store can't link same location twice
            // Drop older unique if you had it (safe-check can't be done easily without raw SQL),
            // but we at least add the new one:
            $table->unique(['store_id', 'stock_source_location_id'], 'sss_store_location_unique');
        });
    }

    public function down(): void
    {
        Schema::table('store_stock_sources', function (Blueprint $table) {
            // drop unique + fk + index
            if (Schema::hasColumn('store_stock_sources', 'stock_source_location_id')) {
                $table->dropUnique('sss_store_location_unique');
                $table->dropForeign('sss_location_fk');
                $table->dropIndex('sss_store_location_idx');
                $table->dropColumn('stock_source_location_id');
            }

            // Restore removed columns (minimal; types may need to match your original)
            $restore = [
                'markup_percent' => fn (Blueprint $t) => $t->decimal('markup_percent', 10, 2)->nullable(),
                'lead_time_days' => fn (Blueprint $t) => $t->integer('lead_time_days')->nullable(),
                'min_delivery_days' => fn (Blueprint $t) => $t->integer('min_delivery_days')->nullable(),
                'max_delivery_days' => fn (Blueprint $t) => $t->integer('max_delivery_days')->nullable(),
                'cutoff_time' => fn (Blueprint $t) => $t->string('cutoff_time', 10)->nullable(),
                'pickup_available' => fn (Blueprint $t) => $t->boolean('pickup_available')->default(false),
                'price_multiplier' => fn (Blueprint $t) => $t->decimal('price_multiplier', 12, 4)->nullable(),
                'extra_fee' => fn (Blueprint $t) => $t->decimal('extra_fee', 12, 2)->nullable(),
                'min_order_amount' => fn (Blueprint $t) => $t->decimal('min_order_amount', 12, 2)->nullable(),
                'coverage' => fn (Blueprint $t) => $t->json('coverage')->nullable(),
            ];

            foreach ($restore as $col => $fn) {
                if (! Schema::hasColumn('store_stock_sources', $col)) {
                    $fn($table);
                }
            }
        });
    }
};