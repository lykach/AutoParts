<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasColumn('stores', 'country_name')) {
                // ставлю поруч із country_id (логічно для адреси)
                $table->string('country_name', 120)->nullable()->after('country_id');
            }
        });

        // перенесення даних (якщо стара колонка існує)
        if (Schema::hasColumn('stores', 'country')) {
            DB::statement("UPDATE stores SET country_name = country WHERE country_name IS NULL OR country_name = ''");
        }

        // видаляємо стару колонку
        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasColumn('stores', 'country')) {
                $table->dropColumn('country');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasColumn('stores', 'country')) {
                $table->string('country', 120)->nullable()->after('country_id');
            }
        });

        if (Schema::hasColumn('stores', 'country_name')) {
            DB::statement("UPDATE stores SET country = country_name WHERE country IS NULL OR country = ''");
        }

        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasColumn('stores', 'country_name')) {
                $table->dropColumn('country_name');
            }
        });
    }
};