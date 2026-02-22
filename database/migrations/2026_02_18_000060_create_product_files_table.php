<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_files', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_id')->index();

            // що це за файл: інструкція / сертифікат / схема / інше
            $table->string('type', 32)->default('other')->index();

            // назва для адмінки/фронту
            $table->string('title', 255)->nullable();

            // зберігаємо шлях у storage (public disk)
            $table->string('file_path', 512);

            // метадані
            $table->string('original_name', 255)->nullable();
            $table->string('mime', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            // порядок і “основний”
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_primary')->default(false)->index();

            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')->on('products')
                ->cascadeOnDelete();

            $table->index(['product_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_files');
    }
};
