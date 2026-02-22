<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_files', function (Blueprint $table) {
            // якщо file_path зараз NOT NULL — робимо NULL
            $table->string('file_path', 512)->nullable()->change();

            // ці поля теж логічно nullable, бо для URL їх нема
            $table->string('original_name', 255)->nullable()->change();
            $table->string('mime', 255)->nullable()->change();
            $table->unsignedBigInteger('size_bytes')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_files', function (Blueprint $table) {
            // назад — як було (якщо треба)
            $table->string('file_path', 512)->nullable(false)->change();

            $table->string('original_name', 255)->nullable(false)->change();
            $table->string('mime', 255)->nullable(false)->change();
            $table->unsignedBigInteger('size_bytes')->nullable(false)->change();
        });
    }
};
