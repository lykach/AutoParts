<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('stock_source_locations')) {
            return;
        }

        Schema::create('stock_source_locations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('stock_source_id');
            $table->string('code', 64)->nullable();
            $table->string('name', 255);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(100);

            $table->string('country', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('address_line1', 255)->nullable();
            $table->string('address_line2', 255)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            $table->json('settings')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->foreign('stock_source_id')
                ->references('id')->on('stock_sources')
                ->cascadeOnDelete();

            $table->index(['stock_source_id', 'is_active']);
            $table->index(['stock_source_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_source_locations');
    }
};