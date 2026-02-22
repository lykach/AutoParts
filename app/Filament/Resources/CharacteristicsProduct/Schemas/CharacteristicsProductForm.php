<?php

namespace App\Filament\Resources\CharacteristicsProduct\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;

class CharacteristicsProductForm
{
    public static function schema(): array
    {
        return [
            Section::make('Основне')
                ->schema([
                    TextInput::make('code')
                        ->label('Код (slug)')
                        ->helperText('Напр.: oem_number, position_side, width, diameter')
                        ->required()
                        ->maxLength(100)
                        ->regex('/^[a-z0-9_]+$/')
                        ->unique(ignoreRecord: true),

                    TextInput::make('sort')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),

                    Select::make('type')
                        ->label('Тип')
                        ->required()
                        ->options([
                            'text'   => 'Текст',
                            'number' => 'Число',
                            'bool'   => 'Так/Ні',
                            'select' => 'Список (select)',
                        ])
                        ->default('text')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            // якщо не select — multi не має сенсу
                            if ($state !== 'select') {
                                $set('is_multivalue', false);
                            }
                        }),

                    Toggle::make('is_multivalue')
                        ->label('Multi-value (декілька значень)')
                        ->helperText('Тільки для типу "Список (select)". Якщо увімкнено — у товарі можна вибрати кілька значень.')
                        ->default(false)
                        ->visible(fn ($get) => $get('type') === 'select'),

                    Toggle::make('is_visible')
                        ->label('Відображати на фронтенді')
                        ->default(true),

                    Toggle::make('is_filterable')
                        ->label('Використовувати у фільтрах')
                        ->default(false),

                    Toggle::make('is_important')
                        ->label('Важлива (показувати в топі)')
                        ->default(false),

                    TextInput::make('synonyms')
                        ->label('Синоніми (через кому)')
                        ->helperText('Напр.: ширина,width,W')
                        ->maxLength(500),
                ])
                ->columns(2),

            Tabs::make('Мови')
                ->tabs([
                    Tabs\Tab::make('Українська')
                        ->schema([
                            TextInput::make('group_uk')->label('Група (UK)')->maxLength(255),
                            TextInput::make('name_uk')->label('Назва (UK)')->required()->maxLength(255),
                        ])
                        ->columns(2),

                    Tabs\Tab::make('English')
                        ->schema([
                            TextInput::make('group_en')->label('Group (EN)')->maxLength(255),
                            TextInput::make('name_en')->label('Name (EN)')->maxLength(255),
                        ])
                        ->columns(2),

                    Tabs\Tab::make('Русский')
                        ->schema([
                            TextInput::make('group_ru')->label('Группа (RU)')->maxLength(255),
                            TextInput::make('name_ru')->label('Название (RU)')->maxLength(255),
                        ])
                        ->columns(2),
                ]),

            Section::make('Налаштування числа')
                ->schema([
                    TextInput::make('unit')
                        ->label('Одиниця виміру')
                        ->helperText('mm, cm, kg, l, V, W, bar, °C...')
                        ->maxLength(20),

                    TextInput::make('decimals')
                        ->label('Знаків після коми')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(6)
                        ->default(0),

                    TextInput::make('min_value')
                        ->label('Мінімальне значення')
                        ->numeric(),

                    TextInput::make('max_value')
                        ->label('Максимальне значення')
                        ->numeric(),
                ])
                ->columns(2)
                ->visible(fn ($get) => $get('type') === 'number'),
        ];
    }
}