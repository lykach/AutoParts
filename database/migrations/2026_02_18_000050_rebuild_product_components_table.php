<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('product_components');

        Schema::create('product_components', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_id')->index();

            // Порядок виводу 1..N (як у твоєму UI)
            $table->unsignedInteger('position')->default(0)->index();

            // Текстова назва позиції комплекту
            $table->string('title', 255);

            // Артикул компонента (як є) + очищений для пошуку
            $table->string('article_raw', 128)->nullable();
            $table->string('article_norm', 128)->nullable()->index();

            // Скільки штук в комплекті (часто 1, але буває 2 хомути/2 болти і т.д.)
            $table->decimal('qty', 10, 3)->default(1);

            // Опційний коментар (наприклад: "входить тільки в комплект")
            $table->string('note', 255)->nullable();

            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')->on('products')
                ->cascadeOnDelete();

            // щоб не було двох "позиція №2" в одному товарі
            $table->unique(['product_id', 'position'], 'pc_product_position_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_components');
    }
};
