<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasColumn('stores', 'inherit_defaults')) {
                $table->boolean('inherit_defaults')->default(true)->after('parent_id')->index();
            }

            if (! Schema::hasColumn('stores', 'country_id')) {
                $table->foreignId('country_id')->nullable()->after('inherit_defaults')->index();
            }

            if (! Schema::hasColumn('stores', 'currency_id')) {
                $table->foreignId('currency_id')->nullable()->after('country_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasColumn('stores', 'currency_id')) {
                $table->dropColumn('currency_id');
            }
            if (Schema::hasColumn('stores', 'country_id')) {
                $table->dropColumn('country_id');
            }
            if (Schema::hasColumn('stores', 'inherit_defaults')) {
                $table->dropColumn('inherit_defaults');
            }
        });
    }
};
