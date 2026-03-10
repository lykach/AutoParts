<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('main_page_groups', function (Blueprint $table) {
            if (Schema::hasColumn('main_page_groups', 'caption')) {
                $table->dropColumn('caption');
            }
        });
    }

    public function down(): void
    {
        Schema::table('main_page_groups', function (Blueprint $table) {
            $table->string('caption')->nullable()->after('caption_ru');
        });
    }
};