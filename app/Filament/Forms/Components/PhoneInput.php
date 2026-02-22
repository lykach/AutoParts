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
            ->rules([new UkrainianPhone()])
            ->dehydrateStateUsing(function ($state) {
                if (empty($state) || $state === '+38 (0' || $state === '+38 (0)') {
                    return null;
                }

                return UkrainianPhone::normalize($state);
            })
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
