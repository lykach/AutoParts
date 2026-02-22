<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();

            $table->string('code', 10)->nullable()->index();          // UA
            $table->string('iso_code_2', 2)->nullable()->index();     // UA
            $table->string('iso_code_3', 3)->nullable()->index();     // UKR
            $table->unsignedSmallInteger('iso_code_numeric')->nullable()->index();

            $table->string('name_uk')->nullable();
            $table->string('name_en')->nullable();
            $table->string('name_ru')->nullable();

            $table->string('currency_code', 10)->nullable()->index(); // UAH
            $table->string('flag_image')->nullable();                 // flags/xx.webp

            $table->boolean('is_group')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(100)->index();

            $table->timestamps();

            $table->unique(['code'], 'countries_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
