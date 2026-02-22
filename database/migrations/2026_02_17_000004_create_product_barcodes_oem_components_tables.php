<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('barcode', 64);
            $table->string('type', 16)->nullable(); // ean13/ean8/upc/other
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique('barcode');
            $table->index('product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::create('product_oem_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('oem_number_raw', 128);
            $table->string('oem_number_norm', 128)->nullable();
            $table->unsignedBigInteger('manufacturer_id')->nullable(); // OE бренд (опційно)
            $table->timestamps();

            $table->index(['product_id', 'oem_number_norm']);
            $table->index('oem_number_norm');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        // Комплектність (BOM/Kit)
        Schema::create('product_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_product_id');
            $table->unsignedBigInteger('component_product_id');
            $table->decimal('qty', 12, 3)->default(1);
            $table->string('note')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['parent_product_id', 'component_product_id']);
            $table->index('parent_product_id');
            $table->index('component_product_id');

            $table->foreign('parent_product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('component_product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_components');
        Schema::dropIfExists('product_oem_numbers');
        Schema::dropIfExists('product_barcodes');
    }
};
