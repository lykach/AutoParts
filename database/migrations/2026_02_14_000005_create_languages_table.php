<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();

            $table->string('code', 10)->unique(); // uk/en/ru

            $table->string('name_uk')->nullable();
            $table->string('name_en')->nullable();
            $table->string('name_ru')->nullable();

            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();

            // TecDoc fields
            $table->unsignedInteger('lng_id')->nullable()->index();
            $table->unsignedInteger('lng_codepage')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
