<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('characteristic_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('characteristic_id')
                ->constrained('characteristics_products')
                ->cascadeOnDelete();

            // Нормалізоване "машинне" значення (для пошуку/унікальності)
            $table->string('value_key', 190)->nullable(); // напр. "left", "front", "2007", "66"

            // Значення для фронту (3 мови) — як label
            $table->string('value_uk')->nullable();
            $table->string('value_en')->nullable();
            $table->string('value_ru')->nullable();

            // Типізовані значення (для number/bool)
            $table->decimal('value_number', 12, 4)->nullable();
            $table->boolean('value_bool')->nullable();

            // Службове
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Швидкі вибірки
            $table->index(['characteristic_id', 'sort']);
            $table->index(['characteristic_id', 'is_active']);

            // Унікальність значень всередині характеристики:
            // Для select/text — value_key (або value_uk якщо key нема)
            $table->unique(['characteristic_id', 'value_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characteristic_values');
    }
};
