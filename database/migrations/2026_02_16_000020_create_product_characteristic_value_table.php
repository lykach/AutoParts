<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_characteristic_value', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('characteristic_id')
                ->constrained('characteristics_products')
                ->cascadeOnDelete();

            $table->foreignId('characteristic_value_id')
                ->constrained('characteristic_values')
                ->cascadeOnDelete();

            $table->unsignedInteger('position')->default(0);
            $table->string('source', 30)->nullable();

            $table->timestamps();

            // ✅ короткі імена індексів (інакше MySQL 64 символи ламається)
            $table->index(['product_id', 'characteristic_id'], 'pcv_prod_char_idx');
            $table->index(['characteristic_id', 'characteristic_value_id'], 'pcv_char_val_idx');

            // ✅ коротка назва UNIQUE
            $table->unique(
                ['product_id', 'characteristic_id', 'characteristic_value_id'],
                'pcv_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_characteristic_value');
    }
};
