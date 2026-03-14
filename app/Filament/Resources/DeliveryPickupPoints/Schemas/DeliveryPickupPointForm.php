<?php

namespace App\Filament\Resources\DeliveryPickupPoints\Schemas;

use App\Models\DeliveryPickupPoint;
use App\Models\Store;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class DeliveryPickupPointForm
{
    protected static function getStore(?int $storeId): ?Store
    {
        if (! $storeId) {
            return null;
        }

        return Store::query()->find($storeId);
    }

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
                                ->live()
                                ->options(fn () => Store::query()
                                    ->orderByDesc('is_main')
                                    ->orderBy('sort_order')
                                    ->pluck('name_uk', 'id')
                                    ->all()
                                )
                                ->helperText('Телефон, адреса і графік можуть братися напряму з цього магазину. Тоді при зміні магазину вони оновляться автоматично.'),

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
                    Section::make('Телефон')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            Toggle::make('settings.inherit.phone')
                                ->label('Брати телефон із магазину')
                                ->default(true)
                                ->live()
                                ->helperText('Рекомендовано. Тоді номер автоматично оновлюється після змін у магазині.'),

                            TextInput::make('phone')
                                ->label('Власний телефон точки')
                                ->maxLength(50)
                                ->disabled(fn ($get) => (bool) $get('settings.inherit.phone'))
                                ->helperText(function ($get) {
                                    if (! (bool) $get('settings.inherit.phone')) {
                                        return 'Використовується власний телефон точки самовивозу.';
                                    }

                                    $store = static::getStore((int) ($get('store_id') ?? 0));
                                    $phone = DeliveryPickupPoint::extractPrimaryPhoneFromStore($store);

                                    return $phone
                                        ? "Зараз буде використано номер магазину: {$phone}"
                                        : 'У магазині номер не заповнений.';
                                }),
                        ]),

                    Section::make('Адреса')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            Toggle::make('settings.inherit.address_uk')
                                ->label('Брати адресу із магазину')
                                ->default(true)
                                ->live()
                                ->helperText('Рекомендовано. Адреса завжди буде актуальною після змін у магазині.'),

                            TextInput::make('address_uk')
                                ->label('Власна адреса (UK)')
                                ->maxLength(255)
                                ->disabled(fn ($get) => (bool) $get('settings.inherit.address_uk'))
                                ->helperText(function ($get) {
                                    if (! (bool) $get('settings.inherit.address_uk')) {
                                        return 'Використовується власна адреса точки самовивозу.';
                                    }

                                    $store = static::getStore((int) ($get('store_id') ?? 0));
                                    $address = DeliveryPickupPoint::buildAddressUkFromStore($store);

                                    return $address
                                        ? "Зараз буде використано адресу магазину: {$address}"
                                        : 'У магазині адреса не заповнена.';
                                }),

                            TextInput::make('address_en')
                                ->label('Адреса (EN)')
                                ->maxLength(255),

                            TextInput::make('address_ru')
                                ->label('Адреса (RU)')
                                ->maxLength(255),
                        ]),
                ]),

                Section::make('Графік роботи')
                    ->schema([
                        Toggle::make('settings.inherit.work_schedule_uk')
                            ->label('Брати графік і святкові дні із магазину')
                            ->default(true)
                            ->live()
                            ->helperText('Рекомендовано. Підтягується регулярний графік + святкові дні / винятки з магазину.'),

                        Textarea::make('work_schedule_uk')
                            ->label('Власний графік (UK)')
                            ->rows(10)
                            ->disabled(fn ($get) => (bool) $get('settings.inherit.work_schedule_uk'))
                            ->helperText(function ($get) {
                                if (! (bool) $get('settings.inherit.work_schedule_uk')) {
                                    return 'Використовується власний графік точки самовивозу.';
                                }

                                $store = static::getStore((int) ($get('store_id') ?? 0));
                                $schedule = DeliveryPickupPoint::buildWorkScheduleUkFromStore($store);

                                return $schedule
                                    ? 'Зараз використовується живий графік магазину, включно зі святковими днями.'
                                    : 'У магазині графік ще не заповнений.';
                            }),

                        Textarea::make('work_schedule_en')
                            ->label('Графік (EN)')
                            ->rows(4),

                        Textarea::make('work_schedule_ru')
                            ->label('Графік (RU)')
                            ->rows(4),
                    ]),

                Section::make('Додатково')
                    ->schema([
                        KeyValue::make('settings')
                            ->label('settings')
                            ->default([])
                            ->helperText('Службові налаштування точки самовивозу. Блок inherit використовується для живого успадкування від магазину.'),
                    ]),
            ]);
    }
}