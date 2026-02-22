<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('article_analog_exports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // all|cross|anti
            $table->string('type', 16)->default('all');

            $table->boolean('only_active')->default(false);

            // queued|processing|done|failed
            $table->string('status', 16)->default('queued');

            $table->unsignedBigInteger('rows')->default(0);

            // public disk path: exports/article_analogs/xxx.csv
            $table->string('disk', 32)->default('public');
            $table->string('path', 255)->nullable();
            $table->string('file_name', 255)->nullable();

            $table->text('error')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['type', 'only_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_analog_exports');
    }
};
