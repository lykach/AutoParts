<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // Важливо: у тебе parent_id може бути -1
            $table->integer('parent_id')->default(-1)->index();

            $table->unsignedInteger('order')->default(1)->index();
            $table->unsignedBigInteger('tecdoc_id')->nullable()->index();

            $table->string('slug')->unique();

            $table->string('name_uk');
            $table->string('name_en')->nullable();
            $table->string('name_ru')->nullable();

            $table->text('description_uk')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ru')->nullable();

            $table->string('meta_title_uk')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->string('meta_title_ru')->nullable();

            $table->text('meta_description_uk')->nullable();
            $table->text('meta_description_en')->nullable();
            $table->text('meta_description_ru')->nullable();

            $table->string('image')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_leaf')->default(false)->index();

            $table->timestamps();

            $table->index(['parent_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
