<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_items', 'pack_qty')) {
                $table->unsignedInteger('pack_qty')->default(1)->after('reserved_qty');
                $table->index('pack_qty');
            }

            if (!Schema::hasColumn('stock_items', 'availability_status')) {
                $table->string('availability_status', 32)->default('in_stock')->after('pack_qty');
                $table->index('availability_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            if (Schema::hasColumn('stock_items', 'availability_status')) {
                $table->dropIndex(['availability_status']);
                $table->dropColumn('availability_status');
            }

            if (Schema::hasColumn('stock_items', 'pack_qty')) {
                $table->dropIndex(['pack_qty']);
                $table->dropColumn('pack_qty');
            }
        });
    }
};
