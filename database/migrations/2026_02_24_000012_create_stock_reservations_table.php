<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_item_id');

            $table->unsignedBigInteger('order_id')->nullable();
            $table->decimal('qty', 12, 3);
            $table->string('status', 32)->default('active'); // active/canceled/fulfilled
            $table->dateTime('expires_at')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('stock_item_id')
                ->references('id')
                ->on('stock_items')
                ->onDelete('cascade');

            $table->index(['stock_item_id', 'status']);
            $table->index(['order_id']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};