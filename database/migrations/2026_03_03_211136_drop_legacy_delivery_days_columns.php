<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Перелив legacy -> нові поля (лише там, де нові ще пусті)
        if (Schema::hasTable('stock_items')) {
            $hasLegacyMin = Schema::hasColumn('stock_items', 'delivery_days_min');
            $hasLegacyMax = Schema::hasColumn('stock_items', 'delivery_days_max');
            $hasNewMin    = Schema::hasColumn('stock_items', 'delivery_min');
            $hasNewMax    = Schema::hasColumn('stock_items', 'delivery_max');

            if ($hasLegacyMin && $hasNewMin) {
                DB::table('stock_items')
                    ->whereNull('delivery_min')
                    ->whereNotNull('delivery_days_min')
                    ->update(['delivery_min' => DB::raw('delivery_days_min')]);
            }

            if ($hasLegacyMax && $hasNewMax) {
                DB::table('stock_items')
                    ->whereNull('delivery_max')
                    ->whereNotNull('delivery_days_max')
                    ->update(['delivery_max' => DB::raw('delivery_days_max')]);
            }

            // Якщо unit порожній, а ми щойно підлили min/max — логічно вважати days
            if (Schema::hasColumn('stock_items', 'delivery_unit')) {
                DB::table('stock_items')
                    ->whereNull('delivery_unit')
                    ->where(function ($q) {
                        $q->whereNotNull('delivery_min')->orWhereNotNull('delivery_max');
                    })
                    ->update(['delivery_unit' => 'days']);
            }
        }

        if (Schema::hasTable('products')) {
            $hasLegacyMin = Schema::hasColumn('products', 'best_delivery_days_min');
            $hasLegacyMax = Schema::hasColumn('products', 'best_delivery_days_max');
            $hasNewMin    = Schema::hasColumn('products', 'best_delivery_min');
            $hasNewMax    = Schema::hasColumn('products', 'best_delivery_max');

            if ($hasLegacyMin && $hasNewMin) {
                DB::table('products')
                    ->whereNull('best_delivery_min')
                    ->whereNotNull('best_delivery_days_min')
                    ->update(['best_delivery_min' => DB::raw('best_delivery_days_min')]);
            }

            if ($hasLegacyMax && $hasNewMax) {
                DB::table('products')
                    ->whereNull('best_delivery_max')
                    ->whereNotNull('best_delivery_days_max')
                    ->update(['best_delivery_max' => DB::raw('best_delivery_days_max')]);
            }

            if (Schema::hasColumn('products', 'best_delivery_unit')) {
                DB::table('products')
                    ->whereNull('best_delivery_unit')
                    ->where(function ($q) {
                        $q->whereNotNull('best_delivery_min')->orWhereNotNull('best_delivery_max');
                    })
                    ->update(['best_delivery_unit' => 'days']);
            }
        }

        // 2) Дроп колонок (guard'имося hasColumn, щоб не падало на різних середовищах)
        if (Schema::hasTable('stock_items')) {
            Schema::table('stock_items', function (Blueprint $table) {
                if (Schema::hasColumn('stock_items', 'delivery_days_min')) {
                    $table->dropColumn('delivery_days_min');
                }
                if (Schema::hasColumn('stock_items', 'delivery_days_max')) {
                    $table->dropColumn('delivery_days_max');
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'best_delivery_days_min')) {
                    $table->dropColumn('best_delivery_days_min');
                }
                if (Schema::hasColumn('products', 'best_delivery_days_max')) {
                    $table->dropColumn('best_delivery_days_max');
                }
            });
        }
    }

    public function down(): void
    {
        // Повертаємо колонки назад (типи поставив integer nullable)
        if (Schema::hasTable('stock_items')) {
            Schema::table('stock_items', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_items', 'delivery_days_min')) {
                    $table->integer('delivery_days_min')->nullable()->after('delivery_max');
                }
                if (!Schema::hasColumn('stock_items', 'delivery_days_max')) {
                    $table->integer('delivery_days_max')->nullable()->after('delivery_days_min');
                }
            });

            // Можна (опційно) налити назад із нових у legacy
            DB::table('stock_items')
                ->whereNull('delivery_days_min')
                ->whereNotNull('delivery_min')
                ->update(['delivery_days_min' => DB::raw('delivery_min')]);

            DB::table('stock_items')
                ->whereNull('delivery_days_max')
                ->whereNotNull('delivery_max')
                ->update(['delivery_days_max' => DB::raw('delivery_max')]);
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'best_delivery_days_min')) {
                    $table->integer('best_delivery_days_min')->nullable()->after('best_delivery_max');
                }
                if (!Schema::hasColumn('products', 'best_delivery_days_max')) {
                    $table->integer('best_delivery_days_max')->nullable()->after('best_delivery_days_min');
                }
            });

            DB::table('products')
                ->whereNull('best_delivery_days_min')
                ->whereNotNull('best_delivery_min')
                ->update(['best_delivery_days_min' => DB::raw('best_delivery_min')]);

            DB::table('products')
                ->whereNull('best_delivery_days_max')
                ->whereNotNull('best_delivery_max')
                ->update(['best_delivery_days_max' => DB::raw('best_delivery_max')]);
        }
    }
};