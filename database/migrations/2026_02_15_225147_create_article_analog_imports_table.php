<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('article_analog_imports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // cross|anti
            $table->string('type', 16);

            $table->boolean('is_active')->default(true);

            // queued|processing|done|failed
            $table->string('status', 16)->default('queued');

            $table->unsignedBigInteger('inserted')->default(0);
            $table->unsignedBigInteger('skipped')->default(0);

            $table->string('disk', 32)->default('local');
            $table->string('path', 255)->nullable();
            $table->string('file_name', 255)->nullable();

            $table->text('error')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_analog_imports');
    }
};
