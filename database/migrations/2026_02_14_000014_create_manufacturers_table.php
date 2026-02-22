<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('logo')->nullable();

            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();

            $table->string('website_url')->nullable();
            $table->string('catalog_url')->nullable();

            $table->text('description_uk')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ru')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(100)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturers');
    }
};
