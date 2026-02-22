<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Language;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            [
                'code' => 'uk',
                'name_uk' => 'Українська',
                'name_en' => 'Ukrainian',
                'name_ru' => 'Украинская',
                'is_default' => true,
                'is_active' => true,
                'lng_id' => 48,      // ✅ TecDoc Language ID
                'lng_codepage' => 1251, // ✅ TecDoc Codepage
            ],
            [
                'code' => 'en',
                'name_uk' => 'Англійська',
                'name_en' => 'English',
                'name_ru' => 'Английский',
                'is_default' => false,
                'is_active' => true,
                'lng_id' => 4,       // ✅ TecDoc Language ID
                'lng_codepage' => 1252, // ✅ TecDoc Codepage
            ],
            [
                'code' => 'ru',
                'name_uk' => 'Руська',
                'name_en' => 'Russian',
                'name_ru' => 'Русский',
                'is_default' => false,
                'is_active' => true,
                'lng_id' => 16,      // ✅ TecDoc Language ID
                'lng_codepage' => 1251, // ✅ TecDoc Codepage
            ],
        ];
        
        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['code' => $language['code']],
                $language
            );
        }
    }
}