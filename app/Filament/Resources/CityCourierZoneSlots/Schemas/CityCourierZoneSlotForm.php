<?php

namespace App\Filament\Resources\CityCourierZoneSlots\Schemas;

use App\Models\CityCourierZone;
use App\Models\CityCourierZoneSlot;
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

class CityCourierZoneSlotForm
{
    protected static function hasOverlap(
        int $zoneId,
        array $days,
        string $from,
        string $to,
        ?int $ignoreId = null
    ): bool {
        $existing = CityCourierZoneSlot::query()
            ->where('city_courier_zone_id', $zoneId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->get([
                'id',
                'work_days',
                'delivery_time_from',
                'delivery_time_to',
            ]);

        foreach ($existing as $row) {
            $daysOverlap = CityCourierZoneSlot::daysOverlap(
                $days,
                is_array($row->work_days) ? $row->work_days : []
            );

            if (! $daysOverlap) {
                continue;
            }

            $timeOverlap = CityCourierZoneSlot::intervalsOverlap(
                $from,
                $to,
                (string) $row->delivery_time_from,
                (string) $row->delivery_time_to,
            );

            if ($timeOverlap) {
                return true;
            }
        }

        return false;
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
                            Select::make('city_courier_zone_id')
                                ->label('Зона курʼєра *')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->options(fn () => CityCourierZone::query()
                                    ->with('store')
                                    ->orderBy('sort_order')
                                    ->orderBy('name_uk')
                                    ->get()
                                    ->mapWithKeys(fn ($row) => [
                                        $row->id => $row->name_uk . ' [' . ($row->store?->name_uk ?? ('#' . $row->store_id)) . ']',
                                    ])
                                    ->all()
                                ),

                            TextInput::make('name')
                                ->label('Назва слота')
                                ->maxLength(150)
                                ->placeholder('Напр.: Ранкова доставка'),

                            Toggle::make('is_active')
                                ->label('Активно')
                                ->default(true),

                            TextInput::make('sort_order')
                                ->label('Порядок сортування')
                                ->numeric()
                                ->default(100),
                        ]),

                    Section::make('Графік слота')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            TimePicker::make('delivery_time_from')
                                ->label('Доставка від *')
                                ->required()
                                ->seconds(false),

                            TimePicker::make('delivery_time_to')
                                ->label('Доставка до *')
                                ->required()
                                ->seconds(false)
                                ->rules([
                                    function ($get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $from = $get('delivery_time_from');
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

                            CheckboxList::make('work_days')
                                ->label('Робочі дні *')
                                ->required()
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
                                ->default(['mon', 'tue', 'wed', 'thu', 'fri', 'sat'])
                                ->rules([
                                    function () {
                                        return function (string $attribute, $value, \Closure $fail) {
                                            $days = is_array($value) ? $value : [];

                                            if ($days === []) {
                                                $fail('Потрібно вибрати хоча б один робочий день.');
                                            }
                                        };
                                    },
                                ]),
                        ]),
                ]),

                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Same day')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            Toggle::make('same_day_enabled')
                                ->label('Same day доступний')
                                ->default(true),

                            TimePicker::make('same_day_cutoff_at')
                                ->label('Cutoff для same day')
                                ->seconds(false)
                                ->helperText('Після цього часу цей слот уже не пропонується як доставка сьогодні.'),
                        ]),

                    Section::make('Внутрішня інформація')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            Textarea::make('manager_note')
                                ->label('Примітка для менеджера')
                                ->rows(4)
                                ->maxLength(5000),
                        ]),
                ]),

                Section::make('Перевірка перетину слотів')
                    ->schema([
                        TextInput::make('overlap_guard')
                            ->label('Службова перевірка')
                            ->dehydrated(false)
                            ->hiddenLabel()
                            ->rules([
                                function ($get, $record) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                        $zoneId = $get('city_courier_zone_id');
                                        $from = $get('delivery_time_from');
                                        $to = $get('delivery_time_to');
                                        $days = $get('work_days');

                                        if (! filled($zoneId) || ! filled($from) || ! filled($to)) {
                                            return;
                                        }

                                        if ((string) $to <= (string) $from) {
                                            return;
                                        }

                                        $days = is_array($days) ? $days : [];
                                        if ($days === []) {
                                            return;
                                        }

                                        $hasOverlap = static::hasOverlap(
                                            zoneId: (int) $zoneId,
                                            days: $days,
                                            from: (string) $from,
                                            to: (string) $to,
                                            ignoreId: $record?->id ? (int) $record->id : null,
                                        );

                                        if ($hasOverlap) {
                                            $fail('Цей слот перетинається з іншим слотом у цій зоні по днях та часу.');
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