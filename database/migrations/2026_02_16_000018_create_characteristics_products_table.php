<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('characteristics_products', function (Blueprint $table) {
            $table->id();

            // Службове
            $table->string('code')->unique();          // наприклад: color, width, oem_number
            $table->unsignedInteger('sort')->default(0);

            // Для фронту (3 мови)
            $table->string('name_uk');
            $table->string('name_en')->nullable();
            $table->string('name_ru')->nullable();

            // Тип і поведінка
            $table->string('type')->default('text');   // text|number|bool|select
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_visible')->default(true);

            // Для select: список опцій (JSON)
            $table->json('options')->nullable();       // ["S","M","L"] або [{"value":"1","label_uk":"..."}...]

            $table->timestamps();

            $table->index(['sort', 'is_visible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characteristics_products');
    }
};
