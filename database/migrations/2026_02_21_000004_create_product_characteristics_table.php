<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_characteristics', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('characteristic_id');

            // Для type=select (словник)
            $table->unsignedBigInteger('characteristic_value_id')->nullable();

            // Для type=text (3 мови)
            $table->string('value_text_uk', 255)->nullable();
            $table->string('value_text_en', 255)->nullable();
            $table->string('value_text_ru', 255)->nullable();

            // Для type=number / type=bool
            $table->decimal('value_number', 14, 4)->nullable();
            $table->boolean('value_bool')->nullable();

            $table->integer('sort')->default(0);

            $table->timestamps();

            // Один товар має одну характеристику один раз
            $table->unique(['product_id', 'characteristic_id'], 'uniq_prod_characteristic');

            // Індекси під фільтрацію
            $table->index(['characteristic_id'], 'idx_pc_characteristic_id');
            $table->index(['characteristic_value_id'], 'idx_pc_characteristic_value_id');
            $table->index(['characteristic_id', 'value_number'], 'idx_pc_char_number');
            $table->index(['characteristic_id', 'value_bool'], 'idx_pc_char_bool');

            $table->foreign('product_id', 'fk_pc_product')
                ->references('id')->on('products')
                ->cascadeOnDelete();

            $table->foreign('characteristic_id', 'fk_pc_characteristic')
                ->references('id')->on('characteristics_products')
                ->cascadeOnDelete();

            $table->foreign('characteristic_value_id', 'fk_pc_characteristic_value')
                ->references('id')->on('characteristic_values')
                ->nullOnDelete(); // якщо значення видалили — в товарі лишиться null
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_characteristics');
    }
};