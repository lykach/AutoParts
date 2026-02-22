<?php

namespace App\Filament\Resources\CharacteristicValues\Schemas;

use App\Models\CharacteristicsProduct;
use App\Models\CharacteristicValue;
use App\Support\CharacteristicValueKey;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Illuminate\Validation\Rule;

class CharacteristicValueForm
{
    public static function schema(): array
    {
        return [
            Section::make('Основне')
                ->schema([
                    Toggle::make('auto_key')
                        ->label('Автоключ (value_key)')
                        ->default(true)
                        ->dehydrated(false)
                        ->live(),

                    Select::make('characteristic_id')
                        ->label('Характеристика')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(fn () => CharacteristicsProduct::query()
                            ->orderBy('name_uk')
                            ->pluck('name_uk', 'id')
                            ->toArray()
                        )
                        ->live(),

                    TextInput::make('value_key')
                        ->label('Ключ (value_key)')
                        ->maxLength(190)
                        ->disabled(fn ($get) => (bool) $get('auto_key'))
                        ->rules([
                            fn (?CharacteristicValue $record, $get) => Rule::unique('characteristic_values', 'value_key')
                                ->where('characteristic_id', (int) ($get('characteristic_id') ?? 0))
                                ->ignore($record?->id),
                        ]),

                    TextInput::make('sort')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),

                    Toggle::make('is_active')
                        ->label('Активне')
                        ->default(true),
                ])
                ->columns(2),

            // type helper (беремо через relationship)
            Section::make('Значення')
                ->schema([
                    // TEXT/SELECT
                    Tabs::make('Мови (text/select)')
                        ->tabs([
                            Tabs\Tab::make('Українська')
                                ->schema([
                                    Textarea::make('value_uk')
                                        ->label('Значення (UK)')
                                        ->rows(2)
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set, $get) {
                                            if (! $get('auto_key')) return;
                                            if (! empty($get('value_key'))) return;
                                            $set('value_key', CharacteristicValueKey::fromText($state, $get('value_en')));
                                        }),
                                ]),
                            Tabs\Tab::make('English')
                                ->schema([
                                    Textarea::make('value_en')
                                        ->label('Value (EN)')
                                        ->rows(2)
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set, $get) {
                                            if (! $get('auto_key')) return;
                                            if (! empty($get('value_key'))) return;
                                            $set('value_key', CharacteristicValueKey::fromText($get('value_uk'), $state));
                                        }),
                                ]),
                            Tabs\Tab::make('Русский')
                                ->schema([
                                    Textarea::make('value_ru')
                                        ->label('Значение (RU)')
                                        ->rows(2)
                                        ->maxLength(255),
                                ]),
                        ])
                        ->visible(fn ($get) => in_array(self::typeOf($get('characteristic_id')), ['text', 'select'], true)),

                    // NUMBER
                    TextInput::make('value_number')
                        ->label('Число (value_number)')
                        ->numeric()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            if (! $get('auto_key')) return;
                            if (! empty($get('value_key'))) return;

                            $decimals = (int) (CharacteristicsProduct::query()
                                ->whereKey((int) $get('characteristic_id'))
                                ->value('decimals') ?? 0);

                            $key = CharacteristicValueKey::fromNumber($state, $decimals);
                            if ($key !== null) $set('value_key', $key);
                        })
                        ->visible(fn ($get) => self::typeOf($get('characteristic_id')) === 'number'),

                    // BOOL
                    Toggle::make('value_bool')
                        ->label('Так/Ні (value_bool)')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            if (! $get('auto_key')) return;
                            if (! empty($get('value_key'))) return;

                            if ($state === null) return;
                            $set('value_key', $state ? '1' : '0');
                        })
                        ->visible(fn ($get) => self::typeOf($get('characteristic_id')) === 'bool'),
                ]),
        ];
    }

    private static function typeOf($characteristicId): ?string
    {
        $id = (int) ($characteristicId ?? 0);
        if (! $id) return null;

        return CharacteristicsProduct::query()->whereKey($id)->value('type');
    }
}