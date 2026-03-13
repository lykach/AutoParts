<?php

namespace App\Filament\Resources\DeliveryPickupPoints\Schemas;

use App\Models\DeliveryPickupPoint;
use App\Models\Store;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class DeliveryPickupPointForm
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
                            Select::make('store_id')
                                ->label('Магазин / Філія *')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->options(fn () => Store::query()
                                    ->orderByDesc('is_main')
                                    ->orderBy('sort_order')
                                    ->pluck('name_uk', 'id')
                                    ->all()
                                ),

                            TextInput::make('code')
                                ->label('Код')
                                ->maxLength(64)
                                ->helperText('Можна не заповнювати — згенерується автоматично.')
                                ->rule(function (?DeliveryPickupPoint $record) {
                                    return Rule::unique('delivery_pickup_points', 'code')
                                        ->ignore($record?->id);
                                }),

                            Toggle::make('is_active')
                                ->label('Активно')
                                ->default(true),

                            TextInput::make('sort_order')
                                ->label('Порядок сортування')
                                ->numeric()
                                ->default(100),

                            TextInput::make('phone')
                                ->label('Телефон')
                                ->maxLength(50),
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
                        ]),
                ]),

                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Адреса')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            TextInput::make('address_uk')
                                ->label('Адреса (UK)')
                                ->maxLength(255),

                            TextInput::make('address_en')
                                ->label('Адреса (EN)')
                                ->maxLength(255),

                            TextInput::make('address_ru')
                                ->label('Адреса (RU)')
                                ->maxLength(255),
                        ]),

                    Section::make('Графік роботи')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            Textarea::make('work_schedule_uk')
                                ->label('Графік (UK)')
                                ->rows(3),

                            Textarea::make('work_schedule_en')
                                ->label('Графік (EN)')
                                ->rows(3),

                            Textarea::make('work_schedule_ru')
                                ->label('Графік (RU)')
                                ->rows(3),
                        ]),
                ]),

                Section::make('Додатково')
                    ->schema([
                        KeyValue::make('settings')
                            ->label('settings')
                            ->default([])
                            ->helperText('Резерв під майбутні налаштування точки самовивозу.'),
                    ]),
            ]);
    }
}