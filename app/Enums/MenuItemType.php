<?php

namespace App\Enums;

enum MenuItemType: string
{
    case Page = 'page';
    case Url = 'url';
    case Category = 'category';
    case Manufacturer = 'manufacturer';

    public function label(): string
    {
        return match ($this) {
            self::Page => 'Сторінка',
            self::Url => 'URL',
            self::Category => 'Категорія',
            self::Manufacturer => 'Виробник',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}