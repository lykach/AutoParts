<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characteristic_values', function (Blueprint $table) {
            // На випадок якщо індекс уже існує у когось вручну — тоді міграція впаде.
            // Якщо ти не певен — скажи, я дам "safe" варіант з перевіркою через DB::select.
            $table->unique(['characteristic_id', 'value_key'], 'uniq_char_value_key');

            $table->index(['characteristic_id', 'is_active'], 'idx_cv_char_active');
            $table->index(['characteristic_id', 'sort'], 'idx_cv_char_sort');
        });
    }

    public function down(): void
    {
        Schema::table('characteristic_values', function (Blueprint $table) {
            $table->dropUnique('uniq_char_value_key');
            $table->dropIndex('idx_cv_char_active');
            $table->dropIndex('idx_cv_char_sort');
        });
    }
};