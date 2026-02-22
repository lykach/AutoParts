<?php

namespace Database\Seeders;

use App\Models\CharacteristicsProduct;
use Illuminate\Database\Seeder;

class CharacteristicValuesSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            // Сторона встановлення
            'position_side' => [
                ['uk' => 'Ліва',       'key' => 'left',  'en' => 'Left',       'ru' => 'Левая'],
                ['uk' => 'Права',      'key' => 'right', 'en' => 'Right',      'ru' => 'Правая'],
                ['uk' => 'Ліва/Права', 'key' => 'both',  'en' => 'Left/Right', 'ru' => 'Левая/Правая'],
            ],

            // Вісь
            'axle' => [
                ['uk' => 'Передня', 'key' => 'front', 'en' => 'Front', 'ru' => 'Передняя'],
                ['uk' => 'Задня',   'key' => 'rear',  'en' => 'Rear',  'ru' => 'Задняя'],
            ],

            // Позиція
            'position' => [
                ['uk' => 'Внутрішня', 'key' => 'inner', 'en' => 'Inner', 'ru' => 'Внутренняя'],
                ['uk' => 'Зовнішня',  'key' => 'outer', 'en' => 'Outer', 'ru' => 'Наружная'],
            ],

            // Тип коробки
            'gearbox' => [
                ['uk' => 'Механічна',  'key' => 'manual',    'en' => 'Manual',    'ru' => 'Механика'],
                ['uk' => 'Автоматична','key' => 'automatic', 'en' => 'Automatic', 'ru' => 'Автомат'],
            ],

            // Паливо
            'fuel_type' => [
                ['uk' => 'Бензин',   'key' => 'petrol',   'en' => 'Petrol',   'ru' => 'Бензин'],
                ['uk' => 'Дизель',   'key' => 'diesel',   'en' => 'Diesel',   'ru' => 'Дизель'],
                ['uk' => 'Гібрид',   'key' => 'hybrid',   'en' => 'Hybrid',   'ru' => 'Гибрид'],
                ['uk' => 'Електро',  'key' => 'electric', 'en' => 'Electric', 'ru' => 'Электро'],
            ],

            // Матеріал (часто потрібно)
            'material' => [
                ['uk' => 'Метал',          'key' => 'metal',          'en' => 'Metal',          'ru' => 'Металл'],
                ['uk' => 'Гума',           'key' => 'rubber',         'en' => 'Rubber',         'ru' => 'Резина'],
                ['uk' => 'Гума/метал',     'key' => 'rubber_metal',   'en' => 'Rubber/Metal',   'ru' => 'Резина/металл'],
                ['uk' => 'Пластик',        'key' => 'plastic',        'en' => 'Plastic',        'ru' => 'Пластик'],
            ],
        ];

        foreach ($map as $code => $values) {
            $characteristic = CharacteristicsProduct::query()
                ->where('code', $code)
                ->first();

            if (! $characteristic) {
                // Характеристики може ще не бути — пропускаємо
                continue;
            }

            foreach ($values as $i => $v) {
                $characteristic->values()->updateOrCreate(
                    ['value_key' => $v['key']],
                    [
                        'value_uk' => $v['uk'],
                        'value_en' => $v['en'] ?? null,
                        'value_ru' => $v['ru'] ?? null,
                        'sort' => ($i + 1) * 10,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
