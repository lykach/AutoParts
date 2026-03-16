<?php

namespace App\Filament\Resources\CityCourierZones\RelationManagers;

use App\Models\CityCourierSlotException;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

class ExceptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'exceptions';

    protected static ?string $title = 'Винятки слотів';

    protected static ?string $modelLabel = 'виняток';
    protected static ?string $pluralModelLabel = 'винятки';

    public function form(Schema $schema): Schema
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
                                ->options(function () {
                                    return $this->getOwnerRecord()
                                        ->slots()
                                        ->orderBy('sort_order')
                                        ->orderBy('id')
                                        ->get()
                                        ->mapWithKeys(function ($row) {
                                            $slot = $row->name ?: (($row->delivery_time_from ?? '—') . '–' . ($row->delivery_time_to ?? '—'));

                                            return [
                                                $row->id => $slot,
                                            ];
                                        })
                                        ->all();
                                }),

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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('exception_date')
            ->columns([
                Tables\Columns\TextColumn::make('slot_view')
                    ->label('Слот')
                    ->state(function ($record): string {
                        return $record->slot?->name
                            ?: (($record->slot?->delivery_time_from ?? '—') . '–' . ($record->slot?->delivery_time_to ?? '—'));
                    })
                    ->description(function ($record): string {
                        $zone = $record->slot?->zone?->name_uk ?: '—';

                        return $zone . ' / ' . ($record->slot?->zone?->city_uk ?: '—');
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('exception_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_closed')
                    ->label('Закрито')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('time_override')
                    ->label('Override часу')
                    ->state(function ($record): string {
                        $from = $record->override_delivery_time_from;
                        $to = $record->override_delivery_time_to;

                        if (! $from && ! $to) {
                            return '—';
                        }

                        return ($from ?: '—') . '–' . ($to ?: '—');
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('override_price')
                    ->label('Override ціни')
                    ->state(fn ($record) => filled($record->override_price)
                        ? number_format((float) $record->override_price, 2, '.', ' ') . ' ₴'
                        : '—'),

                Tables\Columns\TextColumn::make('override_eta')
                    ->label('Override ETA')
                    ->state(function ($record): string {
                        $min = $record->override_eta_min_minutes;
                        $max = $record->override_eta_max_minutes;

                        if (! filled($min) && ! filled($max)) {
                            return '—';
                        }

                        if (filled($min) && filled($max)) {
                            return $min == $max ? "{$min} хв" : "{$min}–{$max} хв";
                        }

                        return (string) ($min ?? $max) . ' хв';
                    }),

                Tables\Columns\TextColumn::make('max_orders')
                    ->label('Ліміт')
                    ->state(fn ($record) => filled($record->max_orders) ? (string) $record->max_orders : '—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Створити виняток')
                    ->using(function (array $data, string $model): Model {
                        try {
                            return $model::query()->create($data);
                        } catch (UniqueConstraintViolationException) {
                            Notification::make()
                                ->title('Виняток уже існує')
                                ->body('Для цього слота вже є виняток на цю дату.')
                                ->warning()
                                ->send();

                            throw new \RuntimeException('Duplicate city courier slot exception.');
                        }
                    })
                    ->after(function () {
                        Notification::make()
                            ->title('Виняток створено')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->using(function (Model $record, array $data): Model {
                        try {
                            $record->update($data);

                            return $record;
                        } catch (UniqueConstraintViolationException) {
                            Notification::make()
                                ->title('Дубль винятку')
                                ->body('Для цього слота вже є виняток на цю дату.')
                                ->warning()
                                ->send();

                            throw new \RuntimeException('Duplicate city courier slot exception.');
                        }
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Видалити вибране')
                    ->requiresConfirmation(),
            ])
            ->defaultSort('exception_date', 'desc')
            ->emptyStateHeading('Винятків ще немає')
            ->emptyStateDescription('Створи перший виняток саме для слотів цієї зони.');
    }
}