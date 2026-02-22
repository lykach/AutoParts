<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('article_analogs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('article', 128);
            $table->string('manufacturer_article', 128);

            $table->string('analog', 128);
            $table->string('manufacturer_analog', 128);

            $table->enum('type', ['cross', 'anti'])->default('cross');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // 🔥 Індекси для швидкого пошуку
            $table->index(['article', 'manufacturer_article'], 'aa_article_mfr_idx');
            $table->index(['analog', 'manufacturer_analog'], 'aa_analog_mfr_idx');
            $table->index(['type', 'is_active'], 'aa_type_active_idx');

            // 🔥 Захист від дублів
            $table->unique(
                ['article', 'manufacturer_article', 'analog', 'manufacturer_analog', 'type'],
                'aa_unique_pair_type'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_analogs');
    }
};
