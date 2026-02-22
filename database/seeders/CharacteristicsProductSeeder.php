<?php

namespace Database\Seeders;

use App\Models\CharacteristicsProduct;
use Illuminate\Database\Seeder;

class CharacteristicsProductSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // Ідентифікація
            [
                'code' => 'oem_number',
                'sort' => 10,
                'group_uk' => 'Ідентифікація',
                'name_uk' => 'OEM номер',
                'name_en' => 'OEM number',
                'name_ru' => 'OEM номер',
                'type' => 'text',
                'is_visible' => true,
                'is_filterable' => true,
                'is_important' => true,
                'synonyms' => 'oem,оригінальний номер,номер oem',
            ],
            [
                'code' => 'manufacturer_part_number',
                'sort' => 20,
                'group_uk' => 'Ідентифікація',
                'name_uk' => 'Артикул виробника',
                'name_en' => 'Manufacturer part number',
                'name_ru' => 'Артикул производителя',
                'type' => 'text',
                'is_visible' => true,
                'is_filterable' => true,
                'is_important' => true,
                'synonyms' => 'mpn,артикул,part number',
            ],
            [
                'code' => 'barcode',
                'sort' => 30,
                'group_uk' => 'Ідентифікація',
                'name_uk' => 'Штрихкод',
                'name_en' => 'Barcode',
                'name_ru' => 'Штрихкод',
                'type' => 'text',
                'is_visible' => false,
                'is_filterable' => false,
                'is_important' => false,
                'synonyms' => 'ean,штрих код,barcode',
            ],

            // Монтаж/позиція
            [
                'code' => 'position_side',
                'sort' => 100,
                'group_uk' => 'Монтаж',
                'name_uk' => 'Сторона встановлення',
                'name_en' => 'Fitting position (side)',
                'name_ru' => 'Сторона установки',
                'type' => 'select',
                'is_visible' => true,
                'is_filterable' => true,
                'is_important' => true,
                'options' => [
                    ['value' => 'left',  'label_uk' => 'Ліва',  'label_en' => 'Left',  'label_ru' => 'Левая'],
                    ['value' => 'right', 'label_uk' => 'Права', 'label_en' => 'Right', 'label_ru' => 'Правая'],
                    ['value' => 'both',  'label_uk' => 'Ліва/Права', 'label_en' => 'Left/Right', 'label_ru' => 'Левая/Правая'],
                ],
            ],
            [
                'code' => 'axle',
                'sort' => 110,
                'group_uk' => 'Монтаж',
                'name_uk' => 'Вісь',
                'name_en' => 'Axle',
                'name_ru' => 'Ось',
                'type' => 'select',
                'is_visible' => true,
                'is_filterable' => true,
                'is_important' => true,
                'options' => [
                    ['value' => 'front', 'label_uk' => 'Передня', 'label_en' => 'Front', 'label_ru' => 'Передняя'],
                    ['value' => 'rear',  'label_uk' => 'Задня',   'label_en' => 'Rear',  'label_ru' => 'Задняя'],
                ],
            ],

            // Розміри/параметри
            [
                'code' => 'length',
                'sort' => 200,
                'group_uk' => 'Розміри',
                'name_uk' => 'Довжина',
                'name_en' => 'Length',
                'name_ru' => 'Длина',
                'type' => 'number',
                'unit' => 'mm',
                'decimals' => 0,
                'is_visible' => true,
                'is_filterable' => true,
                'is_important' => false,
                'synonyms' => 'довжина,length,L',
            ],
            [
                'code' => 'width',
                'sort' => 210,
                'group_uk' => 'Розміри',
                'name_uk' => 'Ширина',
                'name_en' => 'Width',
                'name_ru' => 'Ширина',
                'type' => 'number',
                'unit' => 'mm',
                'decimals' => 0,
                'is_visible' => true,
                'is_filterable' => true,
                'is_important' => false,
                'synonyms' => 'ширина,width,W',
            ],
            [
                'code' => 'height',
                'sort' => 220,
                'group_uk' => 'Розміри',
                'name_uk' => 'Висота',
                'name_en' => 'Height',
                'name_ru' => 'Высота',
                'type' => 'number',
                'unit' => 'mm',
                'decimals' => 0,
                'is_visible' => true,
                'is_filterable' => true,
                'is_important' => false,
                'synonyms' => 'висота,height,H',
            ],
            [
                'code' => 'diameter',
                'sort' => 230,
                'group_uk' => 'Розміри',
                'name_uk' => 'Діаметр',
                'name_en' => 'Diameter',
                'name_ru' => 'Диаметр',
                'type' => 'number',
                'unit' => 'mm',
                'decimals' => 0,
                'is_visible' => true,
                'is_filterable' => true,
                'is_important' => false,
                'synonyms' => 'діаметр,diameter,D,⌀',
            ],

            // Електрика
            [
                'code' => 'voltage',
                'sort' => 300,
                'group_uk' => 'Електрика',
                'name_uk' => 'Напруга',
                'name_en' => 'Voltage',
                'name_ru' => 'Напряжение',
                'type' => 'number',
                'unit' => 'V',
                'decimals' => 0,
                'is_visible' => true,
                'is_filterable' => true,
                'is_important' => false,
                'synonyms' => 'напруга,voltage,V',
            ],
            [
                'code' => 'power',
                'sort' => 310,
                'group_uk' => 'Електрика',
                'name_uk' => 'Потужність',
                'name_en' => 'Power',
                'name_ru' => 'Мощность',
                'type' => 'number',
                'unit' => 'W',
                'decimals' => 0,
                'is_visible' => true,
                'is_filterable' => false,
                'is_important' => false,
                'synonyms' => 'потужність,power,W',
            ],
        ];

        foreach ($items as $item) {
            CharacteristicsProduct::updateOrCreate(
                ['code' => $item['code']],
                array_merge([
                    'sort' => 0,
                    'type' => 'text',
                    'is_visible' => true,
                    'is_filterable' => false,
                    'is_important' => false,
                ], $item)
            );
        }
    }
}
