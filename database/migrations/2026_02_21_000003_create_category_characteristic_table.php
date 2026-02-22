<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_characteristic', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('characteristic_id');

            $table->integer('sort')->default(0);

            // overrides для конкретної категорії (null = брати з характеристики)
            $table->boolean('is_filterable')->nullable();
            $table->boolean('is_visible')->nullable();

            $table->timestamps();

            $table->unique(['category_id', 'characteristic_id'], 'uniq_category_characteristic');

            $table->index('characteristic_id', 'idx_cc_characteristic_id');
            $table->index('category_id', 'idx_cc_category_id');

            $table->foreign('category_id', 'fk_cc_category')
                ->references('id')->on('categories')
                ->cascadeOnDelete();

            $table->foreign('characteristic_id', 'fk_cc_characteristic')
                ->references('id')->on('characteristics_products')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_characteristic');
    }
};