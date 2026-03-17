<?php

namespace App\Enums;

enum PageTemplate: string
{
    case Default = 'default';
    case Contacts = 'contacts';
    case DeliveryPayment = 'delivery_payment';
    case UsefulInfo = 'useful_info';
    case About = 'about';
    case Warranty = 'warranty';
    case Returns = 'returns';

    public function label(): string
    {
        return match ($this) {
            self::Default => 'Стандартна сторінка',
            self::Contacts => 'Контакти',
            self::DeliveryPayment => 'Доставка і оплата',
            self::UsefulInfo => 'Корисна інформація',
            self::About => 'Про нас',
            self::Warranty => 'Гарантія',
            self::Returns => 'Повернення',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}