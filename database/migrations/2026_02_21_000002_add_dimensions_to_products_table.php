<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Вага (кг)
            $table->decimal('weight_kg', 8, 3)->nullable()->after('tecdoc_id');

            // Габарити (см)
            $table->decimal('length_cm', 8, 1)->nullable()->after('weight_kg');
            $table->decimal('width_cm', 8, 1)->nullable()->after('length_cm');
            $table->decimal('height_cm', 8, 1)->nullable()->after('width_cm');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['weight_kg', 'length_cm', 'width_cm', 'height_cm']);
        });
    }
};