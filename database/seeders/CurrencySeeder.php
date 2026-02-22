<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            [
                'code' => 'UAH',
                'iso_code' => '980',
                'symbol' => '₴',
                'short_name_uk' => 'грн',
                'short_name_en' => 'UAH',
                'short_name_ru' => 'грн',
                'rate' => 1.0000,
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'code' => 'USD',
                'iso_code' => '840',
                'symbol' => '$',
                'short_name_uk' => 'дол',
                'short_name_en' => 'USD',
                'short_name_ru' => 'долл',
                'rate' => 41.5000,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'EUR',
                'iso_code' => '978',
                'symbol' => '€',
                'short_name_uk' => 'євро',
                'short_name_en' => 'EUR',
                'short_name_ru' => 'евро',
                'rate' => 45.2000,
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}