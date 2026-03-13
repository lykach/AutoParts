<?php

namespace App\Filament\Resources\PickupPointStoreStockSources\Schemas;

use App\Models\DeliveryPickupPoint;
use App\Models\PickupPointStoreStockSource;
use App\Models\StoreStockSource;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PickupPointStoreStockSourceForm
{
    protected static function buildStoreStockSourceTree(?int $pickupPointId = null): array
    {
        $query = StoreStockSource::query()
            ->with(['store', 'stockSource', 'location'])
            ->orderBy('priority')
            ->orderBy('id');

        // Якщо вибрана точка самовивозу — показуємо тільки склади її магазину
        if ($pickupPointId) {
            $storeId = DeliveryPickupPoint::query()
                ->whereKey($pickupPointId)
                ->value('store_id');

            if ($storeId) {
                $query->where('store_id', (int) $storeId);
            }
        }

        $rows = $query->get();

        $grouped = [];

        foreach ($rows as $row) {
            $storeId = (int) $row->store_id;
            $storeName = $row->store?->name_uk ?: ('Магазин #' . $storeId);

            $sourceId = (int) $row->stock_source_id;
            $sourceName = $row->stockSource?->name ?: ('Джерело #' . $sourceId);

            $locationId = (int) $row->stock_source_location_id;
            $locationName = $row->location?->name ?: ('Локація #' . $locationId);
            $city = $row->location?->city ? trim((string) $row->location->city) : null;

            $leafLabel = $locationName;
            if ($city) {
                $leafLabel .= " — {$city}";
            }

            if (! isset($grouped[$storeId])) {
                $grouped[$storeId] = [
                    'name' => $storeName,
                    'value' => "store:{$storeId}",
                    'disabled' => true,
                    'children' => [],
                ];
            }

            if (! isset($grouped[$storeId]['children'][$sourceId])) {
                $grouped[$storeId]['children'][$sourceId] = [
                    'name' => $sourceName,
                    'value' => "source:{$storeId}:{$sourceId}",
                    'disabled' => true,
                    'children' => [],
                ];
            }

            $grouped[$storeId]['children'][$sourceId]['children'][] = [
                'name' => $leafLabel,
                'value' => (string) $row->id,
                'children' => [],
            ];
        }

        $tree = [];

        foreach ($grouped as $storeNode) {
            $storeNode['children'] = array_values($storeNode['children']);
            $tree[] = $storeNode;
        }

        return $tree;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Привʼязка джерела до точки самовивозу')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            Select::make('pickup_point_id')
                                ->label('Точка самовивозу *')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->options(fn () => DeliveryPickupPoint::query()
                                    ->with('store')
                                    ->orderBy('sort_order')
                                    ->orderBy('name_uk')
                                    ->get()
                                    ->mapWithKeys(fn ($row) => [
                                        $row->id => $row->name_uk . ' [' . ($row->store?->name_uk ?? ('#' . $row->store_id)) . ']',
                                    ])
                                    ->all()
                                )
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    $currentSourceId = $get('store_stock_source_id');

                                    if (! filled($currentSourceId) || ! filled($state)) {
                                        return;
                                    }

                                    $storeId = DeliveryPickupPoint::query()
                                        ->whereKey((int) $state)
                                        ->value('store_id');

                                    if (! $storeId) {
                                        $set('store_stock_source_id', null);
                                        return;
                                    }

                                    $belongsToStore = StoreStockSource::query()
                                        ->whereKey((int) $currentSourceId)
                                        ->where('store_id', (int) $storeId)
                                        ->exists();

                                    if (! $belongsToStore) {
                                        $set('store_stock_source_id', null);
                                    }
                                }),

                            SelectTree::make('store_stock_source_id')
                                ->label('Склад магазину *')
                                ->required()
                                ->multiple(false)
                                ->searchable()
                                ->showTags(false)
                                ->getTreeUsing(fn ($get) => static::buildStoreStockSourceTree(
                                    filled($get('pickup_point_id')) ? (int) $get('pickup_point_id') : null
                                ))
                                ->helperText('Після вибору точки самовивозу показуються склади тільки її магазину.')
                                ->afterStateHydrated(function ($state, $set) {
                                    $state = filled($state) ? (string) $state : null;
                                    $set('store_stock_source_id', $state);
                                })
                                ->afterStateUpdated(function ($state, $set) {
                                    $state = filled($state) ? (string) $state : null;
                                    $set('store_stock_source_id', $state);
                                })
                                ->dehydrateStateUsing(
                                    fn ($state) => (filled($state) && is_numeric($state)) ? (int) $state : null
                                )
                                ->rules([
                                    function ($get, $record) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                            $pickupPointId = $get('pickup_point_id');
                                            $storeStockSourceId = $value;

                                            if (! filled($pickupPointId) || ! filled($storeStockSourceId) || ! is_numeric($storeStockSourceId)) {
                                                return;
                                            }

                                            $pickupPoint = DeliveryPickupPoint::query()
                                                ->select(['id', 'store_id'])
                                                ->find((int) $pickupPointId);

                                            if (! $pickupPoint) {
                                                $fail('Точку самовивозу не знайдено.');
                                                return;
                                            }

                                            $source = StoreStockSource::query()
                                                ->select(['id', 'store_id'])
                                                ->find((int) $storeStockSourceId);

                                            if (! $source) {
                                                $fail('Склад магазину не знайдено.');
                                                return;
                                            }

                                            if ((int) $pickupPoint->store_id !== (int) $source->store_id) {
                                                $fail('Можна вибрати тільки склад цього ж магазину, що й точка самовивозу.');
                                                return;
                                            }

                                            $q = PickupPointStoreStockSource::query()
                                                ->where('pickup_point_id', (int) $pickupPointId)
                                                ->where('store_stock_source_id', (int) $storeStockSourceId);

                                            if ($record?->id) {
                                                $q->where('id', '!=', $record->id);
                                            }

                                            if ($q->exists()) {
                                                $fail('Це джерело вже підключено до вибраної точки самовивозу.');
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
                                ->helperText('Менше значення = вище пріоритет.'),
                        ]),

                    Section::make('Час довозу')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
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
                        ]),
                ]),

                Section::make('Додатково')
                    ->schema([
                        KeyValue::make('settings')
                            ->label('settings')
                            ->default([])
                            ->helperText('Резерв під винятки, прапорці, службові параметри.'),
                    ]),
            ]);
    }
}