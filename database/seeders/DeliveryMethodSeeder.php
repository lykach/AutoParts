<?php

namespace Database\Seeders;

use App\Models\DeliveryMethod;
use Illuminate\Database\Seeder;

class DeliveryMethodSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'code' => 'pickup',
                'name_uk' => 'Самовивіз',
                'name_en' => 'Pickup',
                'name_ru' => 'Самовывоз',
                'description_uk' => 'Отримання замовлення в магазині',
                'description_en' => 'Order pickup in store',
                'description_ru' => 'Получение заказа в магазине',
                'type' => 'pickup',
                'is_active' => true,
                'sort_order' => 10,
                'icon' => 'heroicon-o-building-storefront',
                'settings' => [],
            ],
            [
                'code' => 'nova_poshta',
                'name_uk' => 'Нова Пошта',
                'name_en' => 'Nova Poshta',
                'name_ru' => 'Новая Почта',
                'description_uk' => 'Доставка через Нову Пошту',
                'description_en' => 'Delivery via Nova Poshta',
                'description_ru' => 'Доставка через Новую Почту',
                'type' => 'carrier',
                'is_active' => true,
                'sort_order' => 20,
                'icon' => 'heroicon-o-truck',
                'settings' => [],
            ],
            [
                'code' => 'ukrposhta',
                'name_uk' => 'Укрпошта',
                'name_en' => 'Ukrposhta',
                'name_ru' => 'Укрпочта',
                'description_uk' => 'Доставка через Укрпошту',
                'description_en' => 'Delivery via Ukrposhta',
                'description_ru' => 'Доставка через Укрпочту',
                'type' => 'carrier',
                'is_active' => true,
                'sort_order' => 30,
                'icon' => 'heroicon-o-envelope',
                'settings' => [],
            ],
            [
                'code' => 'meest',
                'name_uk' => 'Meest',
                'name_en' => 'Meest',
                'name_ru' => 'Meest',
                'description_uk' => 'Доставка через Meest',
                'description_en' => 'Delivery via Meest',
                'description_ru' => 'Доставка через Meest',
                'type' => 'carrier',
                'is_active' => true,
                'sort_order' => 40,
                'icon' => 'heroicon-o-paper-airplane',
                'settings' => [],
            ],
            [
                'code' => 'city_courier',
                'name_uk' => 'Доставка курʼєром по місту',
                'name_en' => 'City courier delivery',
                'name_ru' => 'Доставка курьером по городу',
                'description_uk' => 'Курʼєрська доставка в межах міста',
                'description_en' => 'Courier delivery within the city',
                'description_ru' => 'Курьерская доставка по городу',
                'type' => 'courier',
                'is_active' => true,
                'sort_order' => 50,
                'icon' => 'heroicon-o-map',
                'settings' => [],
            ],
        ];

        foreach ($rows as $row) {
            DeliveryMethod::query()->updateOrCreate(
                ['code' => $row['code']],
                $row
            );
        }
    }
}