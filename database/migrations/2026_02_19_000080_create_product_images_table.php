<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_id')->index();

            $table->string('image_path', 512);          // storage path on public disk
            $table->string('title', 255)->nullable();   // optional title for admin/front
            $table->string('alt', 255)->nullable();     // SEO alt text

            $table->boolean('is_primary')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')->on('products')
                ->cascadeOnDelete();

            $table->index(['product_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
