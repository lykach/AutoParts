<?php

namespace App\Filament\Resources\CityCourierSlotExceptions\Schemas;

use App\Models\CityCourierSlotException;
use App\Models\CityCourierZoneSlot;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CityCourierSlotExceptionForm
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
                            Select::make('city_courier_zone_slot_id')
                                ->label('Слот доставки *')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->options(fn () => CityCourierZoneSlot::query()
                                    ->with(['zone.store'])
                                    ->orderBy('sort_order')
                                    ->orderBy('id')
                                    ->get()
                                    ->mapWithKeys(function ($row) {
                                        $zone = $row->zone?->name_uk ?: ('#' . $row->city_courier_zone_id);
                                        $store = $row->zone?->store?->name_uk ?: ('#' . ($row->zone?->store_id ?? 0));
                                        $slot = $row->name ?: (($row->delivery_time_from ?? '—') . '–' . ($row->delivery_time_to ?? '—'));

                                        return [
                                            $row->id => "{$zone} [{$store}] / {$slot}",
                                        ];
                                    })
                                    ->all()
                                ),

                            DatePicker::make('exception_date')
                                ->label('Дата *')
                                ->required()
                                ->native(false),

                            Toggle::make('is_closed')
                                ->label('Слот закритий на цю дату')
                                ->default(false),
                        ]),

                    Section::make('Override часу')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            TimePicker::make('override_delivery_time_from')
                                ->label('Час доставки від')
                                ->seconds(false),

                            TimePicker::make('override_delivery_time_to')
                                ->label('Час доставки до')
                                ->seconds(false)
                                ->rules([
                                    function ($get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $from = $get('override_delivery_time_from');
                                            $to = $value;

                                            if (! filled($from) || ! filled($to)) {
                                                return;
                                            }

                                            if ((string) $to <= (string) $from) {
                                                $fail('Час "до" має бути більшим за час "від".');
                                            }
                                        };
                                    },
                                ]),

                            TimePicker::make('override_cutoff_at')
                                ->label('Cutoff override')
                                ->seconds(false),
                        ]),
                ]),

                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Override ціни та ETA')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            TextInput::make('override_price')
                                ->label('Override ціни')
                                ->numeric()
                                ->prefix('₴'),

                            TextInput::make('override_eta_min_minutes')
                                ->label('Override ETA мін, хв')
                                ->numeric(),

                            TextInput::make('override_eta_max_minutes')
                                ->label('Override ETA макс, хв')
                                ->numeric()
                                ->rules([
                                    function ($get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $min = $get('override_eta_min_minutes');
                                            $max = $value;

                                            if (! filled($min) || ! filled($max)) {
                                                return;
                                            }

                                            if ((int) $max < (int) $min) {
                                                $fail('ETA максимум не може бути меншим за ETA мінімум.');
                                            }
                                        };
                                    },
                                ]),
                        ]),

                    Section::make('Ліміти і примітка')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            TextInput::make('max_orders')
                                ->label('Максимум замовлень на дату')
                                ->numeric()
                                ->helperText('Порожньо — без окремого ліміту.'),

                            Textarea::make('manager_note')
                                ->label('Примітка для менеджера')
                                ->rows(5)
                                ->maxLength(5000),
                        ]),
                ]),

                Section::make('Службова перевірка')
                    ->schema([
                        TextInput::make('exception_guard')
                            ->label('guard')
                            ->hiddenLabel()
                            ->dehydrated(false)
                            ->rules([
                                function ($get, $record) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                        $slotId = $get('city_courier_zone_slot_id');
                                        $date = $get('exception_date');

                                        if (! filled($slotId) || ! filled($date)) {
                                            return;
                                        }

                                        $q = CityCourierSlotException::query()
                                            ->where('city_courier_zone_slot_id', (int) $slotId)
                                            ->whereDate('exception_date', $date);

                                        if ($record?->id) {
                                            $q->where('id', '!=', $record->id);
                                        }

                                        if ($q->exists()) {
                                            $fail('Для цього слота вже існує виняток на вибрану дату.');
                                        }
                                    };
                                },
                            ])
                            ->helperText(''),
                    ]),

                Section::make('Додатково')
                    ->schema([
                        KeyValue::make('settings')
                            ->label('settings')
                            ->default([])
                            ->helperText('Тільки для рідкісних службових параметрів.'),
                    ]),
            ]);
    }
}