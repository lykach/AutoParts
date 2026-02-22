<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();

            $table->string('code', 10)->unique();      // UAH
            $table->string('iso_code', 10)->nullable()->index();
            $table->string('symbol', 10)->nullable();

            $table->string('short_name_uk')->nullable();
            $table->string('short_name_en')->nullable();
            $table->string('short_name_ru')->nullable();

            $table->decimal('rate', 12, 4)->default(1.0000);
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('rate_updated_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
