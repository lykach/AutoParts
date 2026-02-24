<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_sources', function (Blueprint $table) {
            // min_order_default_qty прибираємо
            if (Schema::hasColumn('stock_sources', 'min_order_default_qty')) {
                $table->dropColumn('min_order_default_qty');
            }
        });

        // ✅ щоб не було "Data truncated for column 'type'"
        // Переводимо type в VARCHAR (найнадійніше, ніж підганяти ENUM).
        DB::statement("ALTER TABLE `stock_sources` MODIFY `type` VARCHAR(32) NOT NULL");
    }

    public function down(): void
    {
        // Відкат робити не рекомендую (може зламати дані), але залишимо мінімально:
        Schema::table('stock_sources', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_sources', 'min_order_default_qty')) {
                $table->integer('min_order_default_qty')->nullable()->after('sort_order');
            }
        });
        // type назад не чіпаємо
    }
};