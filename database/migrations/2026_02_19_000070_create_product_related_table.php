<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_related', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('related_product_id')->index();

            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('note', 255)->nullable();

            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')->on('products')
                ->cascadeOnDelete();

            $table->foreign('related_product_id')
                ->references('id')->on('products')
                ->cascadeOnDelete();

            $table->unique(['product_id', 'related_product_id'], 'product_related_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_related');
    }
};
