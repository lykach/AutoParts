<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_items', 'reserved_qty')) {
                $table->decimal('reserved_qty', 12, 3)->default(0)->after('qty');
                $table->index('reserved_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            if (Schema::hasColumn('stock_items', 'reserved_qty')) {
                $table->dropIndex(['reserved_qty']);
                $table->dropColumn('reserved_qty');
            }
        });
    }
};
