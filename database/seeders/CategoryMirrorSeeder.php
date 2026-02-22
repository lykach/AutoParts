<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\CategoryMirror;

class CategoryMirrorSeeder extends Seeder
{
    public function run(): void
    {
        // Отримуємо категорії для прикладів
        $oilFilters = Category::where('slug', 'maslyani-filtri')->first();
        $brakePads = Category::where('slug', 'galmivni-kolodki')->first();
        $brakeDiscs = Category::where('slug', 'galmivni-diski')->first();
        $tieRods = Category::where('slug', 'rulovi-tyagi')->first();
        $shockAbsorbers = Category::where('slug', 'amortizatori')->first();
        
        $brakeSystem = Category::where('slug', 'galmivna-sistema')->first();
        $engine = Category::where('slug', 'dvigun')->first();
        $suspension = Category::where('slug', 'pidviska')->first();
        $steering = Category::where('slug', 'rulove-upravlinnya')->first();

        if (!$oilFilters || !$brakePads || !$brakeSystem || !$engine) {
            $this->command->error('Спочатку запустіть CategorySeeder!');
            return;
        }

        // Створюємо віртуальну категорію "Популярні товари" для демонстрації
        $popular = Category::updateOrCreate(
            ['slug' => 'populyarni-tovari'],
            [
                'parent_id' => -1,
                'order' => 6,
                'name_uk' => 'Популярні товари',
                'name_en' => 'Popular Products',
                'name_ru' => 'Популярные товары',
                'is_active' => true,
            ]
        );

        // Створюємо категорію "Технічне обслуговування"
        $maintenance = Category::updateOrCreate(
            ['slug' => 'tehnichne-obslugovuvannya'],
            [
                'parent_id' => -1,
                'order' => 7,
                'name_uk' => 'Технічне обслуговування',
                'name_en' => 'Maintenance',
                'name_ru' => 'Техническое обслуживание',
                'is_active' => true,
            ]
        );

        $mirrors = [
            // ПРИКЛАД 1: Дублікат БЕЗ перейменування
            // Масляні фільтри показуємо також під "Технічне обслуговування"
            [
                'parent_category_id' => $maintenance->id,
                'source_category_id' => $oilFilters->id,
                'custom_name_uk' => null,
                'custom_name_en' => null,
                'custom_name_ru' => null,
                'custom_slug' => null,
                'is_active' => true,
                'sort_order' => 1,
            ],

            // ПРИКЛАД 2: Дублікат З перейменуванням
            // Гальмівні колодки під "Популярні товари" як "ТОП колодки"
            [
                'parent_category_id' => $popular->id,
                'source_category_id' => $brakePads->id,
                'custom_name_uk' => 'ТОП гальмівні колодки',
                'custom_name_en' => 'TOP Brake Pads',
                'custom_name_ru' => 'ТОП тормозные колодки',
                'custom_slug' => 'top-galmivni-kolodki',
                'is_active' => true,
                'sort_order' => 1,
            ],

            // ПРИКЛАД 3: Гальмівні диски в популярних
            [
                'parent_category_id' => $popular->id,
                'source_category_id' => $brakeDiscs->id,
                'custom_name_uk' => 'ТОП гальмівні диски',
                'custom_name_en' => 'TOP Brake Discs',
                'custom_name_ru' => 'ТОП тормозные диски',
                'custom_slug' => 'top-galmivni-diski',
                'is_active' => true,
                'sort_order' => 2,
            ],

            // ПРИКЛАД 4: Рульові тяги під технічне обслуговування
            [
                'parent_category_id' => $maintenance->id,
                'source_category_id' => $tieRods->id,
                'custom_name_uk' => null,
                'custom_name_en' => null,
                'custom_name_ru' => null,
                'custom_slug' => null,
                'is_active' => true,
                'sort_order' => 2,
            ],

            // ПРИКЛАД 5: Амортизатори в популярних (з custom slug)
            [
                'parent_category_id' => $popular->id,
                'source_category_id' => $shockAbsorbers->id,
                'custom_name_uk' => 'Бестселери амортизаторів',
                'custom_name_en' => 'Bestseller Shock Absorbers',
                'custom_name_ru' => 'Бестселлеры амортизаторов',
                'custom_slug' => 'bestseller-amortizatori',
                'is_active' => true,
                'sort_order' => 3,
            ],

            // ПРИКЛАД 6: НЕАКТИВНИЙ дублікат (для тестування)
            [
                'parent_category_id' => $maintenance->id,
                'source_category_id' => $brakeDiscs->id,
                'custom_name_uk' => 'Диски для ТО',
                'custom_name_en' => 'Discs for Maintenance',
                'custom_name_ru' => 'Диски для ТО',
                'custom_slug' => 'to-galmivni-diski',
                'is_active' => false, // Неактивний!
                'sort_order' => 99,
            ],
        ];

        foreach ($mirrors as $mirrorData) {
            CategoryMirror::updateOrCreate(
                [
                    'parent_category_id' => $mirrorData['parent_category_id'],
                    'source_category_id' => $mirrorData['source_category_id'],
                ],
                $mirrorData
            );
        }

        $this->command->info('✅ Створено ' . count($mirrors) . ' дублікатів категорій');
        $this->command->info('📁 Створено 2 додаткові категорії:');
        $this->command->info('   - Популярні товари');
        $this->command->info('   - Технічне обслуговування');
    }
}