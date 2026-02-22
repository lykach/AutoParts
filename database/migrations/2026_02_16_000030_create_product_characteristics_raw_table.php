<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_characteristics_raw', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->string('source', 30)->nullable();   // tecdoc|price|manual
            $table->string('raw_name', 255);
            $table->text('raw_value')->nullable();
            $table->string('raw_unit', 30)->nullable();

            // Якщо з TecDoc приходять ключі/ID — збережи
            $table->string('external_key', 100)->nullable();

            // Результат нормалізації (пізніше)
            $table->foreignId('characteristic_id')->nullable()
                ->constrained('characteristics_products')
                ->nullOnDelete();

            $table->foreignId('characteristic_value_id')->nullable()
                ->constrained('characteristic_values')
                ->nullOnDelete();

            $table->string('status', 20)->default('new'); // new|parsed|failed
            $table->text('error')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['external_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_characteristics_raw');
    }
};
