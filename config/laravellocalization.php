<?php

return [

    'supportedLocales' => [
        'en' => ['name' => 'English',   'script' => 'Latn', 'native' => 'English',    'regional' => 'en_GB'],
        'uk' => ['name' => 'Ukrainian', 'script' => 'Cyrl', 'native' => 'українська', 'regional' => 'uk_UA'],
        'ru' => ['name' => 'Russian',   'script' => 'Cyrl', 'native' => 'русский',    'regional' => 'ru_RU'],
    ],

    // Важливо для вашого запиту: внутрішньо працюємо з 'uk', зовні бачимо 'ua'
    'localesMapping' => [
        'uk' => 'ua',
    ],

    // Визначати мову за браузером при першому візиті
    'useAcceptLanguageHeader' => true,

    // ПРАВИЛО SEO №1: Завжди показувати префікс мови в URL. 
    // Якщо false, то головна буде autokitparts.top/ua. Це краще для індексації різних копій сайту.
    'hideDefaultLocaleInURL' => false,

    // ПРАВИЛО SEO №2: Якщо хтось зайде на /uk/ сторінку, його перекине на /ua/ (через mapping)
    // Це робить Middleware 'localizationRedirect'
    
    'localesOrder' => ['uk', 'en', 'ru'], // Порядок у випадаючому списку на сайті

    'utf8suffix' => env('LARAVELLOCALIZATION_UTF8SUFFIX', '.UTF-8'),

    // Службові адреси, які пакет НЕ має чіпати
    'urlsIgnored' => [
        '/skipped',
        '/admin*',     // Адмінка Filament
        '/livewire*',  // Запити Livewire (щоб не було 404 помилок у скриптах)
        '/assets*',    // Статичні файли
        '/storage*',   // Фото товарів
    ],

    'httpMethodsIgnored' => ['POST', 'PUT', 'PATCH', 'DELETE'],
];