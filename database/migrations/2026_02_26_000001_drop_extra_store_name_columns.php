<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // якщо раптом у БД немає частини колонок — міграція впаде.
            // Я лишаю “чистий” варіант; якщо треба — зроблю safe-варіант через Schema::hasColumn.
            $table->dropColumn([
                'name_en',
                'name_ru',
                'short_name_uk',
                'short_name_en',
                'short_name_ru',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name_uk');
            $table->string('name_ru')->nullable()->after('name_en');

            $table->string('short_name_uk')->nullable()->after('name_ru');
            $table->string('short_name_en')->nullable()->after('short_name_uk');
            $table->string('short_name_ru')->nullable()->after('short_name_en');
        });
    }
};