<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characteristics_products', function (Blueprint $table) {
            if (!Schema::hasColumn('characteristics_products', 'is_multivalue')) {
                $table->boolean('is_multivalue')
                    ->default(false)
                    ->after('type')
                    ->comment('Only for type=select: allow multiple values per product');
            }
        });
    }

    public function down(): void
    {
        Schema::table('characteristics_products', function (Blueprint $table) {
            if (Schema::hasColumn('characteristics_products', 'is_multivalue')) {
                $table->dropColumn('is_multivalue');
            }
        });
    }
};