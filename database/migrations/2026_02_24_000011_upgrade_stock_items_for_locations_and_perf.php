<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            // 1) pack_qty -> multiplicity
            if (Schema::hasColumn('stock_items', 'pack_qty') && !Schema::hasColumn('stock_items', 'multiplicity')) {
                $table->renameColumn('pack_qty', 'multiplicity');
            }

            // 2) location
            if (!Schema::hasColumn('stock_items', 'stock_source_location_id')) {
                $table->unsignedBigInteger('stock_source_location_id')->nullable()->after('stock_source_id');
                $table->index(['stock_source_location_id']);
            }

            // 3) швидкі денорм-поля
            if (!Schema::hasColumn('stock_items', 'available_qty')) {
                $table->decimal('available_qty', 12, 3)->default(0)->after('reserved_qty');
            }
            if (!Schema::hasColumn('stock_items', 'sellable_qty')) {
                $table->decimal('sellable_qty', 12, 3)->default(0)->after('available_qty');
                $table->index(['sellable_qty']);
            }

            if (!Schema::hasColumn('stock_items', 'price_purchase_uah')) {
                $table->decimal('price_purchase_uah', 12, 2)->nullable()->after('price_purchase');
            }
            if (!Schema::hasColumn('stock_items', 'price_sell_uah')) {
                $table->decimal('price_sell_uah', 12, 2)->nullable()->after('price_sell');
                $table->index(['price_sell_uah']);
            }
        });

        // 4) FK на locations (окремо, щоб не впасти якщо таблиця ще не піднята)
        Schema::table('stock_items', function (Blueprint $table) {
            if (Schema::hasColumn('stock_items', 'stock_source_location_id')) {
                $table->foreign('stock_source_location_id')
                    ->references('id')
                    ->on('stock_source_locations')
                    ->nullOnDelete();
            }
        });

        // 5) Проставимо дефолтний склад-локацію для існуючих stock_items:
        // Створимо по 1 локації "Основний" для кожного stock_source (якщо ще нема),
        // і прив’яжемо старі stock_items до цієї локації.
        $sources = DB::table('stock_sources')->select('id', 'name', 'code')->get();
        foreach ($sources as $s) {
            $locId = DB::table('stock_source_locations')
                ->where('stock_source_id', $s->id)
                ->value('id');

            if (!$locId) {
                $locId = DB::table('stock_source_locations')->insertGetId([
                    'stock_source_id' => $s->id,
                    'code' => 'MAIN',
                    'name' => 'Основний склад',
                    'is_active' => 1,
                    'sort_order' => 100,
                    'settings' => json_encode(new stdClass()),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('stock_items')
                ->where('stock_source_id', $s->id)
                ->whereNull('stock_source_location_id')
                ->update(['stock_source_location_id' => $locId]);
        }

        // 6) Гарантуємо multiplicity >= 1
        DB::table('stock_items')
            ->whereNull('multiplicity')
            ->orWhere('multiplicity', '<', 1)
            ->update(['multiplicity' => 1]);

        // 7) Унікальність: 1 товар = 1 рядок на 1 локації постачальника
        // Спочатку підчистка дублікатів (якщо вони були) — беремо max(id) як "останній"
        // (це обережно, але краще ніж падіння міграції)
        $dups = DB::table('stock_items')
            ->select('stock_source_id', 'stock_source_location_id', 'product_id', DB::raw('COUNT(*) as c'))
            ->groupBy('stock_source_id', 'stock_source_location_id', 'product_id')
            ->having('c', '>', 1)
            ->get();

        foreach ($dups as $d) {
            $ids = DB::table('stock_items')
                ->where('stock_source_id', $d->stock_source_id)
                ->where('stock_source_location_id', $d->stock_source_location_id)
                ->where('product_id', $d->product_id)
                ->orderByDesc('id')
                ->pluck('id')
                ->all();

            array_shift($ids); // лишаємо найбільший id
            if ($ids) {
                DB::table('stock_items')->whereIn('id', $ids)->delete();
            }
        }

        Schema::table('stock_items', function (Blueprint $table) {
            $table->unique(['stock_source_id', 'stock_source_location_id', 'product_id'], 'uniq_stock_source_loc_product');
            $table->index(['product_id', 'stock_source_id']);
            $table->index(['product_id', 'sellable_qty']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            // індекси/унікалка
            if (Schema::hasColumn('stock_items', 'stock_source_id') && Schema::hasColumn('stock_items', 'stock_source_location_id')) {
                $table->dropUnique('uniq_stock_source_loc_product');
            }

            // fk
            if (Schema::hasColumn('stock_items', 'stock_source_location_id')) {
                $table->dropConstrainedForeignId('stock_source_location_id');
                $table->dropColumn('stock_source_location_id');
            }

            // поля
            if (Schema::hasColumn('stock_items', 'available_qty')) $table->dropColumn('available_qty');
            if (Schema::hasColumn('stock_items', 'sellable_qty')) $table->dropColumn('sellable_qty');
            if (Schema::hasColumn('stock_items', 'price_purchase_uah')) $table->dropColumn('price_purchase_uah');
            if (Schema::hasColumn('stock_items', 'price_sell_uah')) $table->dropColumn('price_sell_uah');

            // multiplicity -> pack_qty
            if (Schema::hasColumn('stock_items', 'multiplicity') && !Schema::hasColumn('stock_items', 'pack_qty')) {
                $table->renameColumn('multiplicity', 'pack_qty');
            }
        });
    }
};