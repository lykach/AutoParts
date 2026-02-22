<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'best_price_uah')) {
                $table->decimal('best_price_uah', 12, 2)->nullable()->after('article_norm');
                $table->index('best_price_uah');
            }

            if (!Schema::hasColumn('products', 'best_price_original')) {
                $table->decimal('best_price_original', 12, 2)->nullable()->after('best_price_uah');
            }

            if (!Schema::hasColumn('products', 'best_currency_code')) {
                $table->string('best_currency_code', 3)->nullable()->after('best_price_original');
                $table->index('best_currency_code');
            }

            if (!Schema::hasColumn('products', 'best_stock_source_id')) {
                $table->unsignedBigInteger('best_stock_source_id')->nullable()->after('best_currency_code');
                $table->index('best_stock_source_id');
            }

            if (!Schema::hasColumn('products', 'best_stock_qty')) {
                $table->decimal('best_stock_qty', 12, 3)->nullable()->after('best_stock_source_id');
            }

            if (!Schema::hasColumn('products', 'best_delivery_days_min')) {
                $table->unsignedInteger('best_delivery_days_min')->nullable()->after('best_stock_qty');
            }

            if (!Schema::hasColumn('products', 'best_delivery_days_max')) {
                $table->unsignedInteger('best_delivery_days_max')->nullable()->after('best_delivery_days_min');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach ([
                'best_delivery_days_max',
                'best_delivery_days_min',
                'best_stock_qty',
                'best_stock_source_id',
                'best_currency_code',
                'best_price_original',
                'best_price_uah',
            ] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    // індекси знімемо безпечніше через try-catch не робимо, бо в mysql назви можуть відрізнятись
                    $table->dropColumn($col);
                }
            }
        });
    }
};
