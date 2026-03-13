<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_pickup_points', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('code', 64)->unique();

            $table->string('name_uk', 150);
            $table->string('name_en', 150)->nullable();
            $table->string('name_ru', 150)->nullable();

            $table->string('address_uk', 255)->nullable();
            $table->string('address_en', 255)->nullable();
            $table->string('address_ru', 255)->nullable();

            $table->string('phone', 50)->nullable();

            $table->text('work_schedule_uk')->nullable();
            $table->text('work_schedule_en')->nullable();
            $table->text('work_schedule_ru')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(100);

            $table->json('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'is_active']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_pickup_points');
    }
};