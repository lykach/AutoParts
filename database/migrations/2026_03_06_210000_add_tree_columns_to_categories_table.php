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
            $table->unsignedSmallInteger('depth')->default(0)->after('parent_id');
            $table->string('path_ids', 1024)->nullable()->after('slug');
            $table->string('path_slugs', 2048)->nullable()->after('path_ids');
            $table->unsignedInteger('children_count')->default(0)->after('is_container');

            $table->index('depth', 'categories_depth_idx');
            $table->index(['is_active', 'parent_id', 'order'], 'categories_active_parent_order_idx');
        });

        // На старті виставимо прості значення, потім команда rebuild перерахує все правильно.
        DB::table('categories')->update([
            'depth' => 0,
            'path_ids' => null,
            'path_slugs' => null,
            'children_count' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_depth_idx');
            $table->dropIndex('categories_active_parent_order_idx');

            $table->dropColumn([
                'depth',
                'path_ids',
                'path_slugs',
                'children_count',
            ]);
        });
    }
};