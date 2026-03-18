<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('location')->index();
        });

        DB::table('menus')
            ->whereIn('code', [
                'top-menu',
                'header-main',
                'footer-main',
                'footer-help',
                'mobile-menu',
            ])
            ->update(['is_system' => true]);
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};