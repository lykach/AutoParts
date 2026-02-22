<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_stock_sources', function (Blueprint $table) {
            $table->index(['store_id', 'is_active', 'priority'], 'sss_store_active_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::table('store_stock_sources', function (Blueprint $table) {
            $table->dropIndex('sss_store_active_priority_idx');
        });
    }
};
