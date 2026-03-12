<?php

namespace App\Filament\Resources\DeliveryMethods\Schemas;

use App\Models\DeliveryMethod;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class DeliveryMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Основне')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            TextInput::make('code')
                                ->label('Системний код *')
                                ->required()
                                ->maxLength(50)
                                ->alphaDash()
                                ->helperText('Напр.: pickup, nova_poshta, ukrposhta, meest, city_courier')
                                ->rule(function (?DeliveryMethod $record): Unique {
                                    return \Illuminate\Validation\Rule::unique('delivery_methods', 'code')
                                        ->ignore($record?->id);
                                }),

                            Select::make('type')
                                ->label('Тип доставки *')
                                ->required()
                                ->options([
                                    'pickup' => 'Самовивіз',
                                    'carrier' => 'Служба доставки',
                                    'courier' => 'Курʼєр',
                                ])
                                ->default('carrier'),

                            Toggle::make('is_active')
                                ->label('Активно')
                                ->default(true),

                            TextInput::make('sort_order')
                                ->label('Порядок сортування')
                                ->numeric()
                                ->default(100),

                            TextInput::make('icon')
                                ->label('Іконка')
                                ->maxLength(100)
                                ->placeholder('heroicon-o-truck'),
                        ]),

                    Section::make('Назви для фронтенда')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            TextInput::make('name_uk')
                                ->label('Назва (UK) *')
                                ->required()
                                ->maxLength(150),

                            TextInput::make('name_en')
                                ->label('Назва (EN)')
                                ->maxLength(150),

                            TextInput::make('name_ru')
                                ->label('Назва (RU)')
                                ->maxLength(150),

                            Textarea::make('description_uk')
                                ->label('Опис (UK)')
                                ->rows(3)
                                ->maxLength(500),

                            Textarea::make('description_en')
                                ->label('Опис (EN)')
                                ->rows(3)
                                ->maxLength(500),

                            Textarea::make('description_ru')
                                ->label('Опис (RU)')
                                ->rows(3)
                                ->maxLength(500),
                        ]),
                ]),

                Section::make('Додатково')
                    ->schema([
                        KeyValue::make('settings')
                            ->label('settings')
                            ->default([])
                            ->helperText('Резерв під майбутні налаштування.'),
                    ]),
            ]);
    }
}