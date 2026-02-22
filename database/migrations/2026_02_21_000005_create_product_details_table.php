<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_id');

            // Назва деталі (на фронт)
            $table->string('name_uk', 255);
            $table->string('name_en', 255)->nullable();
            $table->string('name_ru', 255)->nullable();

            // Значення деталі (на фронт)
            $table->text('value_uk')->nullable();
            $table->text('value_en')->nullable();
            $table->text('value_ru')->nullable();

            $table->integer('sort')->default(0);

            // звідки взялось (manual/import/tecdoc)
            $table->string('source', 30)->nullable();

            $table->timestamps();

            $table->index('product_id', 'idx_pd_product_id');
            $table->index(['product_id', 'sort'], 'idx_pd_product_sort');

            $table->foreign('product_id', 'fk_pd_product')
                ->references('id')->on('products')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_details');
    }
};