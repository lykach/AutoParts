<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_sources', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_sources', 'min_order_default_qty')) {
                $table->unsignedInteger('min_order_default_qty')->nullable()->after('sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_sources', function (Blueprint $table) {
            if (Schema::hasColumn('stock_sources', 'min_order_default_qty')) {
                $table->dropColumn('min_order_default_qty');
            }
        });
    }
};
