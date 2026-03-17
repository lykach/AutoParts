<?php

namespace Database\Seeders;

use App\Enums\MenuLocation;
use App\Enums\MenuItemType;
use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'name' => 'Доставка і оплата',
                'slug' => 'delivery-payment',
                'template' => PageTemplate::DeliveryPayment,
                'status' => PageStatus::Published,
                'title_uk' => 'Доставка і оплата',
                'content_uk' => '<h2>Доставка</h2><p>Заповніть контент сторінки.</p><h2>Оплата</h2><p>Заповніть контент сторінки.</p>',
                'is_system' => true,
                'show_in_sitemap' => true,
                'published_at' => now(),
            ],
            [
                'name' => 'Контакти',
                'slug' => 'contacts',
                'template' => PageTemplate::Contacts,
                'status' => PageStatus::Published,
                'title_uk' => 'Контакти',
                'content_uk' => '<h2>Контакти</h2><p>Заповніть телефони, адресу, графік роботи, карту.</p>',
                'is_system' => true,
                'show_in_sitemap' => true,
                'published_at' => now(),
            ],
            [
                'name' => 'Корисна інформація',
                'slug' => 'useful-info',
                'template' => PageTemplate::UsefulInfo,
                'status' => PageStatus::Published,
                'title_uk' => 'Корисна інформація',
                'content_uk' => '<h2>Корисна інформація</h2><p>Заповніть контент сторінки.</p>',
                'is_system' => true,
                'show_in_sitemap' => true,
                'published_at' => now(),
            ],
            [
                'name' => 'Про нас',
                'slug' => 'about-us',
                'template' => PageTemplate::About,
                'status' => PageStatus::Published,
                'title_uk' => 'Про нас',
                'content_uk' => '<h2>Про компанію</h2><p>Заповніть контент сторінки.</p>',
                'is_system' => true,
                'show_in_sitemap' => true,
                'published_at' => now(),
            ],
        ];

        foreach ($pages as $index => $pageData) {
            Page::updateOrCreate(
                ['slug' => $pageData['slug']],
                array_merge($pageData, ['sort' => ($index + 1) * 10])
            );
        }

        $menus = [
            [
                'name' => 'Top меню',
                'code' => 'top-menu',
                'location' => MenuLocation::Top,
                'sort' => 10,
            ],
            [
                'name' => 'Головне меню',
                'code' => 'header-main',
                'location' => MenuLocation::Header,
                'sort' => 20,
            ],
            [
                'name' => 'Footer основне',
                'code' => 'footer-main',
                'location' => MenuLocation::FooterMain,
                'sort' => 30,
            ],
            [
                'name' => 'Footer допомога',
                'code' => 'footer-help',
                'location' => MenuLocation::FooterHelp,
                'sort' => 40,
            ],
            [
                'name' => 'Мобільне меню',
                'code' => 'mobile-menu',
                'location' => MenuLocation::Mobile,
                'sort' => 50,
            ],
        ];

        foreach ($menus as $menuData) {
            Menu::updateOrCreate(
                ['code' => $menuData['code']],
                array_merge($menuData, ['is_active' => true])
            );
        }

        $footerHelp = Menu::where('code', 'footer-help')->first();

        if ($footerHelp) {
            $deliveryPage = Page::where('slug', 'delivery-payment')->first();
            $contactsPage = Page::where('slug', 'contacts')->first();
            $usefulInfoPage = Page::where('slug', 'useful-info')->first();

            $items = [
                [
                    'title_uk' => 'Доставка і оплата',
                    'type' => MenuItemType::Page,
                    'page_id' => $deliveryPage?->id,
                    'sort' => 10,
                ],
                [
                    'title_uk' => 'Контакти',
                    'type' => MenuItemType::Page,
                    'page_id' => $contactsPage?->id,
                    'sort' => 20,
                ],
                [
                    'title_uk' => 'Корисна інформація',
                    'type' => MenuItemType::Page,
                    'page_id' => $usefulInfoPage?->id,
                    'sort' => 30,
                ],
            ];

            foreach ($items as $itemData) {
                MenuItem::updateOrCreate(
                    [
                        'menu_id' => $footerHelp->id,
                        'title_uk' => $itemData['title_uk'],
                    ],
                    array_merge($itemData, [
                        'menu_id' => $footerHelp->id,
                        'is_active' => true,
                    ])
                );
            }
        }
    }
}