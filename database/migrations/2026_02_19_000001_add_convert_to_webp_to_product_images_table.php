<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            if (!Schema::hasColumn('product_images', 'convert_to_webp')) {
                $table->boolean('convert_to_webp')
                    ->default(true)
                    ->after('is_active')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            if (Schema::hasColumn('product_images', 'convert_to_webp')) {
                $table->dropIndex(['convert_to_webp']);
                $table->dropColumn('convert_to_webp');
            }
        });
    }
};
