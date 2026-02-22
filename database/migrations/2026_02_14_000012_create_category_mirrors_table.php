<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('category_mirrors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('source_category_id')->constrained('categories')->cascadeOnDelete();

            $table->string('custom_name_uk')->nullable();
            $table->string('custom_name_en')->nullable();
            $table->string('custom_name_ru')->nullable();

            $table->string('custom_slug')->nullable()->index();

            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(100)->index();

            $table->timestamps();

            $table->unique(['parent_category_id', 'source_category_id'], 'cat_mirror_parent_source_unique');
            $table->index(['parent_category_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_mirrors');
    }
};

