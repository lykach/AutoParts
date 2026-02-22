<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();

            // Tree (головний магазин / філії)
            $table->foreignId('parent_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->boolean('is_main')->default(false)->index();

            // Базове
            $table->string('code', 50)->nullable()->index(); // внутрішній код/ідентифікатор
            $table->string('slug')->unique(); // для роутів / SEO
            $table->string('type', 30)->default('branch')->index(); // main/branch/warehouse/pickup/office/online

            // Назви (uk/en/ru)
            $table->string('name_uk');
            $table->string('name_en')->nullable();
            $table->string('name_ru')->nullable();

            $table->string('short_name_uk')->nullable();
            $table->string('short_name_en')->nullable();
            $table->string('short_name_ru')->nullable();

            // Статус/сортування
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(100)->index();

            // Контакти
            $table->string('email')->nullable()->index();
            $table->string('website_url')->nullable();

            // JSON: телефони/месенджери/соцмережі/додаткові email-и
            $table->json('phones')->nullable();          // [{label, number, is_primary}]
            $table->json('additional_emails')->nullable(); // [{label, email}]
            $table->json('messengers')->nullable();      // {telegram, viber, whatsapp, signal, messenger}
            $table->json('social_links')->nullable();    // {facebook, instagram, tiktok, youtube, x, linkedin}

            // Адреса
            $table->string('country')->nullable();       // Ukraine
            $table->string('region')->nullable();        // Закарпатська область
            $table->string('city')->nullable()->index(); // Ужгород
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->text('address_note')->nullable();    // як знайти, під'їзд, орієнтири

            // Гео
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('google_maps_url')->nullable();
            $table->string('google_place_id')->nullable()->index();

            // Графік (по днях + винятки)
            $table->json('working_hours')->nullable();   // {mon:[{from,to}], tue:..., ...}
            $table->json('working_exceptions')->nullable(); // [{date, is_closed, from, to, note}]

            // Медійка
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();

            // Контент/SEO (uk/en/ru)
            $table->string('title_uk')->nullable();
            $table->string('title_en')->nullable();
            $table->string('title_ru')->nullable();

            $table->text('description_uk')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ru')->nullable();

            $table->string('meta_title_uk')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->string('meta_title_ru')->nullable();

            $table->text('meta_description_uk')->nullable();
            $table->text('meta_description_en')->nullable();
            $table->text('meta_description_ru')->nullable();

            // SEO додатково
            $table->string('canonical_url')->nullable();
            $table->string('robots')->nullable(); // "index,follow" / "noindex,nofollow" etc.
            $table->json('seo')->nullable(); // запас: {og_title, og_desc, og_image, ...}

            // Юридичні
            $table->string('company_name')->nullable();
            $table->string('edrpou', 20)->nullable()->index(); // ЄДРПОУ
            $table->string('vat', 30)->nullable()->index();    // VAT / ІПН
            $table->text('legal_address')->nullable();

            // Системні / “на виріст”
            $table->string('timezone')->nullable(); // Europe/Uzhgorod
            $table->string('currency', 10)->nullable(); // UAH
            $table->string('default_language', 10)->nullable(); // uk
            $table->json('settings')->nullable(); // будь-які майбутні опції
            $table->text('internal_note')->nullable();

            $table->timestamps();

            $table->index(['parent_id', 'is_active', 'sort_order']);
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
