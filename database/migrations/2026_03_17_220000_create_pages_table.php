<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('template')->default('default')->index();
            $table->string('status')->default('draft')->index();

            $table->string('title_uk')->nullable();
            $table->string('title_en')->nullable();
            $table->string('title_ru')->nullable();

            $table->text('excerpt_uk')->nullable();
            $table->text('excerpt_en')->nullable();
            $table->text('excerpt_ru')->nullable();

            $table->longText('content_uk')->nullable();
            $table->longText('content_en')->nullable();
            $table->longText('content_ru')->nullable();

            $table->string('seo_title_uk')->nullable();
            $table->string('seo_title_en')->nullable();
            $table->string('seo_title_ru')->nullable();

            $table->text('seo_description_uk')->nullable();
            $table->text('seo_description_en')->nullable();
            $table->text('seo_description_ru')->nullable();

            $table->json('seo_keywords_uk')->nullable();
            $table->json('seo_keywords_en')->nullable();
            $table->json('seo_keywords_ru')->nullable();

            $table->string('cover_image')->nullable();

            $table->unsignedInteger('sort')->default(0)->index();
            $table->boolean('is_system')->default(false)->index();
            $table->boolean('show_in_sitemap')->default(true)->index();

            $table->timestamp('published_at')->nullable()->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};