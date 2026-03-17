<?php

namespace App\Enums;

enum MenuLocation: string
{
    case Top = 'top';
    case Header = 'header';
    case FooterMain = 'footer_main';
    case FooterHelp = 'footer_help';
    case FooterContacts = 'footer_contacts';
    case Mobile = 'mobile';
    case Help = 'help';

    public function label(): string
    {
        return match ($this) {
            self::Top => 'Top меню',
            self::Header => 'Головне меню',
            self::FooterMain => 'Footer — основне',
            self::FooterHelp => 'Footer — допомога',
            self::FooterContacts => 'Footer — контакти',
            self::Mobile => 'Мобільне меню',
            self::Help => 'Меню допомоги',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}