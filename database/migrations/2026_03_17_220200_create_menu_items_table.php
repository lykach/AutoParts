<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();

            $table->string('title_uk')->nullable();
            $table->string('title_en')->nullable();
            $table->string('title_ru')->nullable();

            $table->string('type')->default('page')->index();

            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('url')->nullable();

            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('manufacturer_id')->nullable()->constrained('manufacturers')->nullOnDelete();

            $table->string('icon')->nullable();
            $table->string('badge_text')->nullable();
            $table->string('badge_color')->nullable();

            $table->boolean('target_blank')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort')->default(0)->index();

            $table->timestamps();

            $table->index(['menu_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};