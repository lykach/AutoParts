<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('products_direct_count')->default(0)->after('children_count');
            $table->unsignedInteger('products_total_count')->default(0)->after('products_direct_count');

            $table->index('products_direct_count', 'categories_products_direct_count_idx');
            $table->index('products_total_count', 'categories_products_total_count_idx');
        });

        DB::table('categories')->update([
            'products_direct_count' => 0,
            'products_total_count' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_products_direct_count_idx');
            $table->dropIndex('categories_products_total_count_idx');

            $table->dropColumn([
                'products_direct_count',
                'products_total_count',
            ]);
        });
    }
};