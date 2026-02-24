<?php

namespace App\Filament\Forms\Components;

use App\Rules\UkrainianPhone;
use Filament\Forms\Components\TextInput;

class PhoneInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Телефон')
            ->tel()
            ->mask('+38 (099) 999-99-99')
            ->placeholder('+38 (0__) ___-__-__')
            ->default('+38 (0')
            ->stripCharacters([' ', '(', ')', '-'])

            /**
             * ✅ КЛЮЧ: валідовати ТІЛЬКИ коли поле реально заповнили
             * (а не коли там просто дефолт "+38 (0")
             */
            ->rule(function ($state) {
                if (empty($state) || $state === '+38 (0' || $state === '+38 (0)') {
                    return 'nullable';
                }

                return [new UkrainianPhone()];
            })

            /**
             * ✅ Якщо не ввели — зберігаємо NULL.
             * Якщо ввели — нормалізуємо.
             */
            ->dehydrateStateUsing(function ($state) {
                if (empty($state) || $state === '+38 (0' || $state === '+38 (0)') {
                    return null;
                }

                return UkrainianPhone::normalize($state);
            })

            /**
             * ✅ Після завантаження: або дефолт маски, або форматований номер
             */
            ->afterStateHydrated(function ($component, $state) {
                if (empty($state)) {
                    $component->state('+38 (0');
                } else {
                    $component->state(UkrainianPhone::format($state));
                }
            })

            ->helperText('Формат: +38 (0XX) XXX-XX-XX');
    }
}