<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'deleted_at')) {
                $table->softDeletes(); // додає deleted_at
                $table->index('deleted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'deleted_at')) {
                // індекс може мати різну назву, тому dropIndex робимо по колонці
                $table->dropIndex(['deleted_at']);
                $table->dropColumn('deleted_at');
            }
        });
    }
};
