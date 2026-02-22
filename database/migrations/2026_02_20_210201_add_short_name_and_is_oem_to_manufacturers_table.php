<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('manufacturers', function (Blueprint $table) {
        $table->string('short_name', 80)->nullable()->after('name');
        $table->boolean('is_oem')->default(false)->after('description_ru');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manufacturers', function (Blueprint $table) {
            //
        });
    }
};
