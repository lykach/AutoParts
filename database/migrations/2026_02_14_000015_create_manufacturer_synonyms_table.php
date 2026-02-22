<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manufacturer_synonyms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('manufacturer_id')->constrained('manufacturers')->cascadeOnDelete();
            $table->string('synonym', 255)->index();

            $table->timestamps();

            $table->unique(['manufacturer_id', 'synonym'], 'manufacturer_syn_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturer_synonyms');
    }
};
