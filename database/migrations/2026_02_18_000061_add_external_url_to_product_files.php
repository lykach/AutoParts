<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_files', function (Blueprint $table) {
            $table->string('source', 16)->default('upload')->index()->after('type');
            $table->string('external_url', 1024)->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('product_files', function (Blueprint $table) {
            $table->dropColumn(['external_url', 'source']);
        });
    }
};
