<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_files', function (Blueprint $table) {
            // потрібно для source=url
            $table->string('file_path', 512)->nullable()->change();

            // на всякий випадок (щоб не було "порожніх" строк)
            $table->string('external_url', 1024)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_files', function (Blueprint $table) {
            // назад як було — але якщо в БД є записи з null, rollback впаде
            $table->string('file_path', 512)->nullable(false)->change();
        });
    }
};
