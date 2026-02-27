<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | STOCK SOURCES
        |--------------------------------------------------------------------------
        */

        Schema::table('stock_sources', function (Blueprint $table) {

            if (Schema::hasColumn('stock_sources', 'region')) {
                $table->dropColumn('region');
            }

            if (Schema::hasColumn('stock_sources', 'address_line2')) {
                $table->dropColumn('address_line2');
            }

            if (Schema::hasColumn('stock_sources', 'postal_code')) {
                $table->dropColumn('postal_code');
            }

            if (Schema::hasColumn('stock_sources', 'lat')) {
                $table->dropColumn('lat');
            }

            if (Schema::hasColumn('stock_sources', 'lng')) {
                $table->dropColumn('lng');
            }
        });

        /*
        |--------------------------------------------------------------------------
        | STOCK SOURCE LOCATIONS
        |--------------------------------------------------------------------------
        */

        Schema::table('stock_source_locations', function (Blueprint $table) {

            if (Schema::hasColumn('stock_source_locations', 'region')) {
                $table->dropColumn('region');
            }

            if (Schema::hasColumn('stock_source_locations', 'address_line2')) {
                $table->dropColumn('address_line2');
            }

            if (Schema::hasColumn('stock_source_locations', 'postal_code')) {
                $table->dropColumn('postal_code');
            }

            if (Schema::hasColumn('stock_source_locations', 'lat')) {
                $table->dropColumn('lat');
            }

            if (Schema::hasColumn('stock_source_locations', 'lng')) {
                $table->dropColumn('lng');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_sources', function (Blueprint $table) {

            if (!Schema::hasColumn('stock_sources', 'region')) {
                $table->string('region')->nullable();
            }

            if (!Schema::hasColumn('stock_sources', 'address_line2')) {
                $table->string('address_line2')->nullable();
            }

            if (!Schema::hasColumn('stock_sources', 'postal_code')) {
                $table->string('postal_code')->nullable();
            }

            if (!Schema::hasColumn('stock_sources', 'lat')) {
                $table->decimal('lat', 10, 7)->nullable();
            }

            if (!Schema::hasColumn('stock_sources', 'lng')) {
                $table->decimal('lng', 10, 7)->nullable();
            }
        });

        Schema::table('stock_source_locations', function (Blueprint $table) {

            if (!Schema::hasColumn('stock_source_locations', 'region')) {
                $table->string('region')->nullable();
            }

            if (!Schema::hasColumn('stock_source_locations', 'address_line2')) {
                $table->string('address_line2')->nullable();
            }

            if (!Schema::hasColumn('stock_source_locations', 'postal_code')) {
                $table->string('postal_code')->nullable();
            }

            if (!Schema::hasColumn('stock_source_locations', 'lat')) {
                $table->decimal('lat', 10, 7)->nullable();
            }

            if (!Schema::hasColumn('stock_source_locations', 'lng')) {
                $table->decimal('lng', 10, 7)->nullable();
            }
        });
    }
};