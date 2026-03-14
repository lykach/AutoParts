<?php

namespace App\Filament\Resources\DeliveryPickupPoints\RelationManagers;

use App\Models\PickupPointStoreStockSource;
use App\Models\StoreStockSource;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PickupSourcesRelationManager extends RelationManager
{
    protected static string $relationship = 'stockSourceLinks';

    protected static ?string $title = 'Джерела самовивозу';

    protected static function buildStoreStockSourceTree(int $storeId): array
    {
        $rows = StoreStockSource::query()
            ->with(['stockSource', 'location'])
            ->where('store_id', $storeId)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        $grouped = [];

        foreach ($rows as $row) {
            $sourceId = (int) $row->stock_source_id;
            $sourceName = $row->stockSource?->name ?: ('Джерело #' . $sourceId);

            $locationId = (int) $row->stock_source_location_id;
            $locationName = $row->location?->name ?: ('Локація #' . $locationId);
            $city = filled($row->location?->city) ? trim((string) $row->location->city) : null;

            $leafLabel = $locationName;
            if ($city) {
                $leafLabel .= " — {$city}";
            }

            if (! isset($grouped[$sourceId])) {
                $grouped[$sourceId] = [
                    'name' => $sourceName,
                    'value' => "source:{$sourceId}",
                    'disabled' => true,
                    'children' => [],
                ];
            }

            $grouped[$sourceId]['children'][] = [
                'name' => $leafLabel,
                'value' => (string) $row->id,
                'children' => [],
            ];
        }

        return array_values($grouped);
    }

    public function form(Schema $schema): Schema
    {
        $ownerStoreId = (int) ($this->ownerRecord->store_id ?? 0);

        return $schema->components([
            SelectTree::make('store_stock_source_id')
                ->label('Склад магазину *')
                ->required()
                ->multiple(false)
                ->searchable()
                ->showTags(false)
                ->getTreeUsing(fn () => static::buildStoreStockSourceTree($ownerStoreId))
                ->helperText('Показуються тільки склади того магазину, до якого належить ця точка самовивозу.')
                ->afterStateHydrated(function ($state, $set) {
                    $set('store_stock_source_id', filled($state) ? (string) $state : null);
                })
                ->afterStateUpdated(function ($state, $set) {
                    $set('store_stock_source_id', filled($state) ? (string) $state : null);
                })
                ->dehydrateStateUsing(
                    fn ($state) => (filled($state) && is_numeric($state)) ? (int) $state : null
                )
                ->rules([
                    function ($record) {
                        return function (string $attribute, $value, \Closure $fail) use ($record) {
                            if (! filled($value) || ! is_numeric($value)) {
                                return;
                            }

                            $source = StoreStockSource::query()
                                ->select(['id', 'store_id'])
                                ->find((int) $value);

                            if (! $source) {
                                $fail('Склад магазину не знайдено.');
                                return;
                            }

                            if ((int) $source->store_id !== (int) $this->ownerRecord->store_id) {
                                $fail('Можна вибрати тільки склад цього ж магазину, що й точка самовивозу.');
                                return;
                            }

                            $q = PickupPointStoreStockSource::query()
                                ->where('pickup_point_id', (int) $this->ownerRecord->id)
                                ->where('store_stock_source_id', (int) $value);

                            if ($record?->id) {
                                $q->where('id', '!=', $record->id);
                            }

                            if ($q->exists()) {
                                $fail('Це джерело вже підключено до цієї точки самовивозу.');
                            }
                        };
                    },
                ]),

            Toggle::make('is_active')
                ->label('Активно')
                ->default(true),

            TextInput::make('priority')
                ->label('Пріоритет')
                ->numeric()
                ->default(100)
                ->helperText('Менше значення = вищий пріоритет.'),

            Select::make('transfer_time_unit')
                ->label('Одиниця часу *')
                ->required()
                ->options([
                    'minute' => 'Хвилини',
                    'hour' => 'Години',
                    'day' => 'Дні',
                ])
                ->default('hour'),

            TextInput::make('transfer_time_min')
                ->label('Мінімум *')
                ->required()
                ->numeric()
                ->default(0),

            TextInput::make('transfer_time_max')
                ->label('Максимум *')
                ->required()
                ->numeric()
                ->default(0),

            TimePicker::make('cutoff_at')
                ->label('Cutoff time')
                ->seconds(false)
                ->helperText('Після цього часу джерело вважаємо вже на наступний день.'),

            Textarea::make('note')
                ->label('Примітка')
                ->rows(3)
                ->maxLength(1000),

            KeyValue::make('settings')
                ->label('settings')
                ->default([])
                ->helperText('Резерв під винятки, прапорці, службові параметри.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store_stock_source_view')
                    ->label('Склад магазину')
                    ->state(function ($record): string {
                        $storeName = $record->storeStockSource?->store?->name_uk
                            ?: ('#' . (int) ($record->storeStockSource?->store_id ?? 0));

                        $sourceName = $record->storeStockSource?->stockSource?->name
                            ?: ('#' . (int) ($record->storeStockSource?->stock_source_id ?? 0));

                        return "{$storeName} / {$sourceName}";
                    })
                    ->description(function ($record): string {
                        $locationName = $record->storeStockSource?->location?->name
                            ?: ('#' . (int) ($record->storeStockSource?->stock_source_location_id ?? 0));

                        $city = filled($record->storeStockSource?->location?->city)
                            ? trim((string) $record->storeStockSource->location->city)
                            : null;

                        return $city ? "{$locationName} — {$city}" : $locationName;
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('eta_view')
                    ->label('Час довозу')
                    ->state(function ($record): string {
                        $unit = match ($record->transfer_time_unit) {
                            'minute' => 'хв',
                            'hour' => 'год',
                            'day' => 'дн',
                            default => (string) $record->transfer_time_unit,
                        };

                        $min = (int) $record->transfer_time_min;
                        $max = (int) $record->transfer_time_max;

                        return $min === $max
                            ? "{$min} {$unit}"
                            : "{$min}–{$max} {$unit}";
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('cutoff_at')
                    ->label('Cutoff')
                    ->state(fn ($record) => $record->cutoff_at ?: '—'),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Пріоритет')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Підключити джерело')
                    ->using(function (array $data) {
                        $data['pickup_point_id'] = (int) $this->ownerRecord->id;

                        $exists = PickupPointStoreStockSource::query()
                            ->where('pickup_point_id', $data['pickup_point_id'])
                            ->where('store_stock_source_id', (int) $data['store_stock_source_id'])
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->title('Джерело вже підключене')
                                ->body('Цей склад магазину вже підключено до вибраної точки самовивозу.')
                                ->warning()
                                ->send();

                            return null;
                        }

                        return PickupPointStoreStockSource::query()->create($data);
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->using(function ($record, array $data) {
                        $exists = PickupPointStoreStockSource::query()
                            ->where('pickup_point_id', (int) $this->ownerRecord->id)
                            ->where('store_stock_source_id', (int) $data['store_stock_source_id'])
                            ->where('id', '!=', $record->id)
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->title('Дубль джерела')
                                ->body('Цей склад магазину вже підключено до цієї точки самовивозу.')
                                ->warning()
                                ->send();

                            return $record;
                        }

                        $record->update($data);

                        return $record;
                    }),

                DeleteAction::make(),
            ])
            ->defaultSort('priority', 'asc')
            ->emptyStateHeading('Для цієї точки джерел ще немає')
            ->emptyStateDescription('Підключи перше джерело самовивозу прямо тут.');
    }
}