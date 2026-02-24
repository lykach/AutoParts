<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Робимо type нормальним VARCHAR, щоб підтримувати supplier_price / supplier_api та інші
        DB::statement("ALTER TABLE `stock_sources` MODIFY `type` VARCHAR(32) NOT NULL");
    }

    public function down(): void
    {
        // Назад не відкотимо коректно, бо не знаємо старий ENUM.
        // Лишаємо як є.
    }
};