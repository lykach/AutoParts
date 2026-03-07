<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('main_page_groups', function (Blueprint $table) {
            $table->id();
            $table->string('caption', 255);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('show_caption')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('main_page_groups');
    }
};