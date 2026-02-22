<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('characteristics_products', function (Blueprint $table) {
            // Група/секція для фронту (3 мови)
            $table->string('group_uk')->nullable()->after('sort');
            $table->string('group_en')->nullable()->after('group_uk');
            $table->string('group_ru')->nullable()->after('group_en');

            // Для числових значень
            $table->string('unit', 20)->nullable()->after('type'); // mm, kg, V, W...
            $table->unsignedTinyInteger('decimals')->default(0)->after('unit'); // 0..4
            $table->decimal('min_value', 12, 4)->nullable()->after('decimals');
            $table->decimal('max_value', 12, 4)->nullable()->after('min_value');

            // Для відображення/пошуку
            $table->boolean('is_important')->default(false)->after('is_filterable');
            $table->string('synonyms', 500)->nullable()->after('is_important'); // "ширина,width,W"
        });
    }

    public function down(): void
    {
        Schema::table('characteristics_products', function (Blueprint $table) {
            $table->dropColumn([
                'group_uk', 'group_en', 'group_ru',
                'unit', 'decimals', 'min_value', 'max_value',
                'is_important', 'synonyms',
            ]);
        });
    }
};
