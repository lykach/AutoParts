<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('main_page_groups', function (Blueprint $table) {

            $table->string('caption_uk')->after('id');
            $table->string('caption_en')->nullable()->after('caption_uk');
            $table->string('caption_ru')->nullable()->after('caption_en');

        });
    }

    public function down(): void
    {
        Schema::table('main_page_groups', function (Blueprint $table) {

            $table->dropColumn([
                'caption_uk',
                'caption_en',
                'caption_ru',
            ]);

        });
    }
};