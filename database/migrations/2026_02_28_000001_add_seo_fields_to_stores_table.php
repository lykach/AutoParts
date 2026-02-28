<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // SEO basics
            $table->string('footer_title_uk')->nullable()->after('name_uk');
            $table->string('footer_title_en')->nullable()->after('footer_title_uk');
            $table->string('footer_title_ru')->nullable()->after('footer_title_en');

            $table->string('h1_uk')->nullable()->after('footer_title_ru');
            $table->string('h1_en')->nullable()->after('h1_uk');
            $table->string('h1_ru')->nullable()->after('h1_en');

            $table->string('meta_keywords_uk', 500)->nullable()->after('meta_title_ru');
            $table->string('meta_keywords_en', 500)->nullable()->after('meta_keywords_uk');
            $table->string('meta_keywords_ru', 500)->nullable()->after('meta_keywords_en');

            // OpenGraph
            $table->string('og_title_uk')->nullable()->after('meta_description_ru');
            $table->string('og_title_en')->nullable()->after('og_title_uk');
            $table->string('og_title_ru')->nullable()->after('og_title_en');

            $table->text('og_description_uk')->nullable()->after('og_title_ru');
            $table->text('og_description_en')->nullable()->after('og_description_uk');
            $table->text('og_description_ru')->nullable()->after('og_description_en');

            $table->string('og_image')->nullable()->after('og_description_ru'); // path on disk public
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'footer_title_uk','footer_title_en','footer_title_ru',
                'h1_uk','h1_en','h1_ru',
                'meta_keywords_uk','meta_keywords_en','meta_keywords_ru',
                'og_title_uk','og_title_en','og_title_ru',
                'og_description_uk','og_description_en','og_description_ru',
                'og_image',
            ]);
        });
    }
};