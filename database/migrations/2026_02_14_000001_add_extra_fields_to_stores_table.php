<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // Локалізація
            if (!Schema::hasColumn('stores', 'timezone')) {
                $table->string('timezone', 64)->nullable()->after('legal_address');
            }
            if (!Schema::hasColumn('stores', 'currency')) {
                $table->string('currency', 8)->nullable()->after('timezone'); // UAH, EUR, USD...
            }
            if (!Schema::hasColumn('stores', 'default_language')) {
                $table->string('default_language', 8)->nullable()->after('currency'); // uk/en/ru
            }

            // Доставка / оплата / самовивіз
            if (!Schema::hasColumn('stores', 'pickup_instructions_uk')) {
                $table->text('pickup_instructions_uk')->nullable()->after('default_language');
                $table->text('pickup_instructions_en')->nullable()->after('pickup_instructions_uk');
                $table->text('pickup_instructions_ru')->nullable()->after('pickup_instructions_en');
            }

            if (!Schema::hasColumn('stores', 'delivery_info_uk')) {
                $table->text('delivery_info_uk')->nullable()->after('pickup_instructions_ru');
                $table->text('delivery_info_en')->nullable()->after('delivery_info_uk');
                $table->text('delivery_info_ru')->nullable()->after('delivery_info_en');
            }

            if (!Schema::hasColumn('stores', 'payment_methods')) {
                $table->json('payment_methods')->nullable()->after('delivery_info_ru');
            }
            if (!Schema::hasColumn('stores', 'delivery_methods')) {
                $table->json('delivery_methods')->nullable()->after('payment_methods');
            }
            if (!Schema::hasColumn('stores', 'services')) {
                $table->json('services')->nullable()->after('delivery_methods'); // шиномонтаж/діагностика/…
            }

            // B2B / опт
            if (!Schema::hasColumn('stores', 'b2b_contacts')) {
                $table->json('b2b_contacts')->nullable()->after('services');
            }

            // Загальні налаштування (на майбутнє)
            if (!Schema::hasColumn('stores', 'settings')) {
                $table->json('settings')->nullable()->after('b2b_contacts');
            }

            // Внутрішня нотатка (для менеджерів)
            if (!Schema::hasColumn('stores', 'internal_note')) {
                $table->text('internal_note')->nullable()->after('settings');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $drop = function (string $col) use ($table) {
                if (Schema::hasColumn('stores', $col)) {
                    $table->dropColumn($col);
                }
            };

            $drop('timezone');
            $drop('currency');
            $drop('default_language');

            $drop('pickup_instructions_uk');
            $drop('pickup_instructions_en');
            $drop('pickup_instructions_ru');

            $drop('delivery_info_uk');
            $drop('delivery_info_en');
            $drop('delivery_info_ru');

            $drop('payment_methods');
            $drop('delivery_methods');
            $drop('services');
            $drop('b2b_contacts');
            $drop('settings');
            $drop('internal_note');
        });
    }
};
