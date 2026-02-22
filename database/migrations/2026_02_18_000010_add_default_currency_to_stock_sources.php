<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_sources', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_sources', 'default_currency_code')) {
                $table->string('default_currency_code', 3)->default('UAH')->after('min_order_default_qty');
                $table->index('default_currency_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_sources', function (Blueprint $table) {
            if (Schema::hasColumn('stock_sources', 'default_currency_code')) {
                $table->dropIndex(['default_currency_code']);
                $table->dropColumn('default_currency_code');
            }
        });
    }
};
