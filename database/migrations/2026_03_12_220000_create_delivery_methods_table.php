<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_methods', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();

            $table->string('name_uk', 150);
            $table->string('name_en', 150)->nullable();
            $table->string('name_ru', 150)->nullable();

            $table->string('description_uk', 500)->nullable();
            $table->string('description_en', 500)->nullable();
            $table->string('description_ru', 500)->nullable();

            $table->string('type', 30); // pickup | carrier | courier

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(100);

            $table->string('icon', 100)->nullable();
            $table->json('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_methods');
    }
};