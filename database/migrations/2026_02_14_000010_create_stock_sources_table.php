<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_sources', function (Blueprint $table) {
            $table->id();

            $table->string('code', 64)->unique(); // internal unique code
            $table->string('name', 255);

            $table->enum('type', ['own_warehouse', 'branch_warehouse', 'supplier_api', 'manual', 'dropship', 'other'])
                ->default('own_warehouse');

            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(100)->index();

            // optional contact/info
            $table->string('contact_name', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('website_url', 255)->nullable();

            // address / geo (optional)
            $table->string('country', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('address_line1', 255)->nullable();
            $table->string('address_line2', 255)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // extra settings
            $table->json('settings')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_sources');
    }
};
