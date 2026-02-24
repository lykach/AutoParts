<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            if (Schema::hasColumn('stock_items', 'pack_qty')) {
                $table->renameColumn('pack_qty', 'multiplicity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            if (Schema::hasColumn('stock_items', 'multiplicity')) {
                $table->renameColumn('multiplicity', 'pack_qty');
            }
        });
    }
};