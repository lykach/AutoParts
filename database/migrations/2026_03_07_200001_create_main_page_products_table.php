<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('main_page_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')
                ->constrained('main_page_groups')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['group_id', 'product_id']);
            $table->index(['group_id', 'sort']);
            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('main_page_products');
    }
};