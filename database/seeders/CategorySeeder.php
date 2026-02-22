<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // КОРЕНЕВІ КАТЕГОРІЇ (parent_id = -1)
            [
                'parent_id' => -1,
                'order' => 1,
                'name_uk' => 'Гальмівна система',
                'name_en' => 'Brake System',
                'name_ru' => 'Тормозная система',
                'slug' => 'galmivna-sistema',
                'is_active' => true,
                'tecdoc_id' => null,
                'children' => [
                    [
                        'order' => 1,
                        'name_uk' => 'Гальмівні колодки',
                        'name_en' => 'Brake Pads',
                        'name_ru' => 'Тормозные колодки',
                        'slug' => 'galmivni-kolodki',
                        'tecdoc_id' => 2101,
                    ],
                    [
                        'order' => 2,
                        'name_uk' => 'Гальмівні диски',
                        'name_en' => 'Brake Discs',
                        'name_ru' => 'Тормозные диски',
                        'slug' => 'galmivni-diski',
                        'tecdoc_id' => 2102,
                    ],
                    [
                        'order' => 3,
                        'name_uk' => 'Гальмівні барабани',
                        'name_en' => 'Brake Drums',
                        'name_ru' => 'Тормозные барабаны',
                        'slug' => 'galmivni-barabani',
                        'tecdoc_id' => 2103,
                    ],
                ],
            ],
            
            [
                'parent_id' => -1,
                'order' => 2,
                'name_uk' => 'Підвіска',
                'name_en' => 'Suspension',
                'name_ru' => 'Подвеска',
                'slug' => 'pidviska',
                'is_active' => true,
                'tecdoc_id' => null,
                'children' => [
                    [
                        'order' => 1,
                        'name_uk' => 'Амортизатори',
                        'name_en' => 'Shock Absorbers',
                        'name_ru' => 'Амортизаторы',
                        'slug' => 'amortizatori',
                        'tecdoc_id' => 3101,
                    ],
                    [
                        'order' => 2,
                        'name_uk' => 'Пружини підвіски',
                        'name_en' => 'Suspension Springs',
                        'name_ru' => 'Пружины подвески',
                        'slug' => 'pruzhini-pidviski',
                        'tecdoc_id' => 3102,
                    ],
                    [
                        'order' => 3,
                        'name_uk' => 'Важелі підвіски',
                        'name_en' => 'Control Arms',
                        'name_ru' => 'Рычаги подвески',
                        'slug' => 'vazheli-pidviski',
                        'tecdoc_id' => 3103,
                    ],
                ],
            ],
            
            [
                'parent_id' => -1,
                'order' => 3,
                'name_uk' => 'Рульове управління',
                'name_en' => 'Steering',
                'name_ru' => 'Рулевое управление',
                'slug' => 'rulove-upravlinnya',
                'is_active' => true,
                'tecdoc_id' => null,
                'children' => [
                    [
                        'order' => 1,
                        'name_uk' => 'Рульові тяги',
                        'name_en' => 'Tie Rods',
                        'name_ru' => 'Рулевые тяги',
                        'slug' => 'rulovi-tyagi',
                        'tecdoc_id' => 4101,
                    ],
                    [
                        'order' => 2,
                        'name_uk' => 'Рульові наконечники',
                        'name_en' => 'Tie Rod Ends',
                        'name_ru' => 'Рулевые наконечники',
                        'slug' => 'rulovi-nakonechniki',
                        'tecdoc_id' => 4102,
                    ],
                    [
                        'order' => 3,
                        'name_uk' => 'Рульові рейки',
                        'name_en' => 'Steering Racks',
                        'name_ru' => 'Рулевые рейки',
                        'slug' => 'rulovi-rejki',
                        'tecdoc_id' => 4103,
                    ],
                ],
            ],
            
            [
                'parent_id' => -1,
                'order' => 4,
                'name_uk' => 'Двигун',
                'name_en' => 'Engine',
                'name_ru' => 'Двигатель',
                'slug' => 'dvigun',
                'is_active' => true,
                'tecdoc_id' => null,
                'children' => [
                    [
                        'order' => 1,
                        'name_uk' => 'Фільтри',
                        'name_en' => 'Filters',
                        'name_ru' => 'Фильтры',
                        'slug' => 'filtri',
                        'tecdoc_id' => null,
                        'children' => [
                            [
                                'order' => 1,
                                'name_uk' => 'Масляні фільтри',
                                'name_en' => 'Oil Filters',
                                'name_ru' => 'Масляные фильтры',
                                'slug' => 'maslyani-filtri',
                                'tecdoc_id' => 5101,
                            ],
                            [
                                'order' => 2,
                                'name_uk' => 'Повітряні фільтри',
                                'name_en' => 'Air Filters',
                                'name_ru' => 'Воздушные фильтры',
                                'slug' => 'povitryani-filtri',
                                'tecdoc_id' => 5102,
                            ],
                            [
                                'order' => 3,
                                'name_uk' => 'Паливні фільтри',
                                'name_en' => 'Fuel Filters',
                                'name_ru' => 'Топливные фильтры',
                                'slug' => 'palivni-filtri',
                                'tecdoc_id' => 5103,
                            ],
                        ],
                    ],
                    [
                        'order' => 2,
                        'name_uk' => 'Ремені і ланцюги',
                        'name_en' => 'Belts & Chains',
                        'name_ru' => 'Ремни и цепи',
                        'slug' => 'remeni-i-lancyugi',
                        'tecdoc_id' => 5201,
                    ],
                ],
            ],
            
            [
                'parent_id' => -1,
                'order' => 5,
                'name_uk' => 'Електрика',
                'name_en' => 'Electrics',
                'name_ru' => 'Электрика',
                'slug' => 'elektrika',
                'is_active' => true,
                'tecdoc_id' => null,
                'children' => [
                    [
                        'order' => 1,
                        'name_uk' => 'Акумулятори',
                        'name_en' => 'Batteries',
                        'name_ru' => 'Аккумуляторы',
                        'slug' => 'akumulyatori',
                        'tecdoc_id' => 6101,
                    ],
                    [
                        'order' => 2,
                        'name_uk' => 'Генератори',
                        'name_en' => 'Alternators',
                        'name_ru' => 'Генераторы',
                        'slug' => 'generatori',
                        'tecdoc_id' => 6102,
                    ],
                    [
                        'order' => 3,
                        'name_uk' => 'Стартери',
                        'name_en' => 'Starters',
                        'name_ru' => 'Стартеры',
                        'slug' => 'starteri',
                        'tecdoc_id' => 6103,
                    ],
                ],
            ],
        ];

        // Рекурсивна функція для створення категорій
        $this->createCategories($categories);
    }

    /**
     * Рекурсивне створення категорій з підкатегоріями
     */
    private function createCategories(array $categories, ?int $parentId = null): void
    {
        foreach ($categories as $categoryData) {
            // Витягуємо children якщо є
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            // Встановлюємо parent_id
            if ($parentId !== null) {
                $categoryData['parent_id'] = $parentId;
            }

            // Створюємо категорію
            $category = Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );

            // Рекурсивно створюємо підкатегорії
            if (!empty($children)) {
                $this->createCategories($children, $category->id);
            }
        }
    }
}