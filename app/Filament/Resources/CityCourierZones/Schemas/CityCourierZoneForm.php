<?php

namespace App\Filament\Resources\CityCourierZones\Schemas;

use App\Models\CityCourierZone;
use App\Models\Store;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class CityCourierZoneForm
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
                                ->rule(function (?CityCourierZone $record) {
                                    return Rule::unique('city_courier_zones', 'code')
                                        ->ignore($record?->id);
                                }),

                            Toggle::make('is_active')
                                ->label('Активно')
                                ->default(true),

                            TextInput::make('sort_order')
                                ->label('Порядок сортування')
                                ->numeric()
                                ->default(100),
                        ]),

                    Section::make('Назви для фронтенда')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            TextInput::make('name_uk')
                                ->label('Назва зони (UK) *')
                                ->required()
                                ->maxLength(150),

                            TextInput::make('name_en')
                                ->label('Назва зони (EN)')
                                ->maxLength(150),

                            TextInput::make('name_ru')
                                ->label('Назва зони (RU)')
                                ->maxLength(150),

                            TextInput::make('city_uk')
                                ->label('Місто (UK) *')
                                ->required()
                                ->maxLength(150),

                            TextInput::make('city_en')
                                ->label('Місто (EN)')
                                ->maxLength(150),

                            TextInput::make('city_ru')
                                ->label('Місто (RU)')
                                ->maxLength(150),
                        ]),
                ]),

                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Тариф і обмеження')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            TextInput::make('delivery_price')
                                ->label('Вартість доставки *')
                                ->required()
                                ->numeric()
                                ->default(0)
                                ->prefix('₴'),

                            TextInput::make('free_from_amount')
                                ->label('Безкоштовно від суми')
                                ->numeric()
                                ->prefix('₴')
                                ->helperText('Порожньо — безкоштовної доставки немає.'),

                            TextInput::make('min_order_amount')
                                ->label('Мінімальна сума замовлення')
                                ->numeric()
                                ->prefix('₴'),

                            TextInput::make('max_order_amount')
                                ->label('Максимальна сума замовлення')
                                ->numeric()
                                ->prefix('₴'),

                            TextInput::make('weight_limit_kg')
                                ->label('Ліміт ваги, кг')
                                ->numeric()
                                ->step('0.001'),
                        ]),

                    Section::make('ETA')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            TextInput::make('eta_min_minutes')
                                ->label('ETA мінімум, хв *')
                                ->required()
                                ->numeric()
                                ->default(60),

                            TextInput::make('eta_max_minutes')
                                ->label('ETA максимум, хв *')
                                ->required()
                                ->numeric()
                                ->default(180),
                        ]),
                ]),

                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Оплата')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            Toggle::make('cash_allowed')
                                ->label('Готівка дозволена')
                                ->default(true),

                            Toggle::make('card_allowed')
                                ->label('Оплата карткою дозволена')
                                ->default(true),

                            Toggle::make('cod_allowed')
                                ->label('Післяплата дозволена')
                                ->default(false),
                        ]),

                    Section::make('Same day і графік')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            Toggle::make('same_day_available')
                                ->label('Доставка в той самий день')
                                ->default(true),

                            TimePicker::make('order_cutoff_at')
                                ->label('Cutoff time')
                                ->seconds(false)
                                ->helperText('Після цього часу same-day вже не діє.'),

                            TimePicker::make('work_time_from')
                                ->label('Початок роботи курʼєра')
                                ->seconds(false),

                            TimePicker::make('work_time_to')
                                ->label('Кінець роботи курʼєра')
                                ->seconds(false),

                            CheckboxList::make('work_days')
                                ->label('Робочі дні')
                                ->columns(4)
                                ->options([
                                    'mon' => 'Пн',
                                    'tue' => 'Вт',
                                    'wed' => 'Ср',
                                    'thu' => 'Чт',
                                    'fri' => 'Пт',
                                    'sat' => 'Сб',
                                    'sun' => 'Нд',
                                ])
                                ->default(['mon', 'tue', 'wed', 'thu', 'fri', 'sat']),
                        ]),
                ]),

                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Опис для фронтенда')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            Textarea::make('description_uk')
                                ->label('Опис (UK)')
                                ->rows(3),

                            Textarea::make('description_en')
                                ->label('Опис (EN)')
                                ->rows(3),

                            Textarea::make('description_ru')
                                ->label('Опис (RU)')
                                ->rows(3),
                        ]),

                    Section::make('Внутрішня примітка')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            Textarea::make('manager_note')
                                ->label('Примітка для менеджера')
                                ->rows(6)
                                ->maxLength(5000),
                        ]),
                ]),

                Section::make('Додатково')
                    ->schema([
                        KeyValue::make('settings')
                            ->label('settings')
                            ->default([])
                            ->helperText('Тільки для рідкісних службових прапорців. Основну логіку тримаємо в окремих полях.'),
                    ]),
            ]);
    }
}