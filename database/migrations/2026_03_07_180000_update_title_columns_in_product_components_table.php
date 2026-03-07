<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_components', function (Blueprint $table) {
            $table->renameColumn('title', 'title_uk');
        });

        Schema::table('product_components', function (Blueprint $table) {
            $table->string('title_en', 255)->nullable()->after('title_uk');
            $table->string('title_ru', 255)->nullable()->after('title_en');
        });
    }

    public function down(): void
    {
        Schema::table('product_components', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'title_ru']);
        });

        Schema::table('product_components', function (Blueprint $table) {
            $table->renameColumn('title_uk', 'title');
        });
    }
};