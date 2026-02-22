<?php

namespace Database\Seeders;

use App\Models\Manufacturer;
use App\Models\ManufacturerSynonym;
use Illuminate\Database\Seeder;

class ManufacturerSeeder extends Seeder
{
    public function run(): void
    {
        $manufacturers = [
            [
                'name' => 'KNECHT',
                'country_id' => 3,
                'description_uk' => 'Knecht – відомий виробник фільтрів та автокомпонентів.',
                'description_en' => 'Knecht is a well-known manufacturer of filters and automotive components.',
                'description_ru' => 'Knecht – известный производитель фильтров и автокомпонентов.',
                'synonyms' => [
                    'KNECHT / MAHLE',
                    'KNECHT - MAHLE',
                    'KNECHT FILTER',
                    'KNECHT FILTERWERKE GMBH',
                    'KNECHT MAHLE',
                    'KNECHT-MAHLE',
                    'KNECHT/MAHLE',
                    'КНЕХТ',
                ],
            ],

            [
                'name' => 'BOSCH',
                'country_id' => 3,
                'description_uk' => 'Bosch – один із найбільших світових виробників автокомпонентів.',
                'description_en' => 'Bosch is one of the largest global manufacturers of automotive components.',
                'description_ru' => 'Bosch – один из крупнейших мировых производителей автокомпонентов.',
                'synonyms' => [
                    'ROBERT BOSCH',
                    'BOSCH AUTO',
                    'БОШ',
                ],
            ],

            [
                'name' => 'MANN-FILTER',
                'country_id' => 3,
                'description_uk' => 'MANN-FILTER – преміальні фільтри для легкових та вантажних авто.',
                'description_en' => 'MANN-FILTER – premium filters for passenger and commercial vehicles.',
                'description_ru' => 'MANN-FILTER – премиальные фильтры для легковых и грузовых авто.',
                'synonyms' => [
                    'MANN FILTER',
                    'MANN+HUMMEL',
                    'МАНН',
                ],
            ],
        ];

        foreach ($manufacturers as $data) {

            $manufacturer = Manufacturer::updateOrCreate(
                ['name' => $data['name']],
                [
                    'country_id' => $data['country_id'] ?? null,
                    'description_uk' => $data['description_uk'] ?? null,
                    'description_en' => $data['description_en'] ?? null,
                    'description_ru' => $data['description_ru'] ?? null,
                    'is_active' => true,
                ]
            );

            // Синоніми
            foreach ($data['synonyms'] as $synonym) {

                ManufacturerSynonym::updateOrCreate(
                    [
                        'manufacturer_id' => $manufacturer->id,
                        'synonym' => mb_strtoupper(trim($synonym), 'UTF-8'),
                    ],
                    []
                );
            }
        }
    }
}
