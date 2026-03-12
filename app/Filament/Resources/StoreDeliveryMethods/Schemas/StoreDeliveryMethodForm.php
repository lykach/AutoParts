<?php

namespace App\Filament\Resources\StoreDeliveryMethods\Schemas;

use App\Models\DeliveryMethod;
use App\Models\Store;
use App\Models\StoreDeliveryMethod;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StoreDeliveryMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Привʼязка доставки до магазину')
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

                            Select::make('delivery_method_id')
                                ->label('Спосіб доставки *')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->options(fn () => DeliveryMethod::query()
                                    ->orderBy('sort_order')
                                    ->orderBy('name_uk')
                                    ->pluck('name_uk', 'id')
                                    ->all()
                                )
                                ->rules([
                                    function ($get, $record) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                            $storeId = $get('store_id');
                                            $deliveryMethodId = $value;

                                            if (! filled($storeId) || ! filled($deliveryMethodId)) {
                                                return;
                                            }

                                            $q = StoreDeliveryMethod::query()
                                                ->where('store_id', (int) $storeId)
                                                ->where('delivery_method_id', (int) $deliveryMethodId);

                                            if ($record?->id) {
                                                $q->where('id', '!=', $record->id);
                                            }

                                            if ($q->exists()) {
                                                $fail('Цей спосіб доставки вже підключено до вибраного магазину.');
                                            }
                                        };
                                    },
                                ]),

                            Toggle::make('is_active')
                                ->label('Активно')
                                ->default(true),

                            TextInput::make('sort_order')
                                ->label('Порядок сортування')
                                ->numeric()
                                ->default(100)
                                ->helperText('Менше значення = вище в списку.'),
                        ]),

                    Section::make('Додатково')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            KeyValue::make('settings')
                                ->label('settings')
                                ->default([])
                                ->helperText('Резерв під майбутні локальні налаштування магазину.'),
                        ]),
                ]),
            ]);
    }
}