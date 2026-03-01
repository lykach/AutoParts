<?php

namespace App\Filament\Resources\StoreStockSources\Schemas;

use App\Models\StockSource;
use App\Models\StockSourceLocation;
use App\Models\Store;
use App\Models\StoreStockSource;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StoreStockSourceForm
{
    protected static function buildSourceLocationTree(): array
    {
        $sources = StockSource::query()
            ->where('is_active', true)
            ->with(['locations' => function ($q) {
                $q->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $tree = [];

        foreach ($sources as $src) {
            $children = [];

            foreach ($src->locations as $loc) {
                $label = $loc->name;
                $city = $loc->city ? trim((string) $loc->city) : null;
                if ($city) $label .= " — {$city}";

                $children[] = [
                    'name' => $label,
                    'value' => (string) $loc->id,
                    'children' => [],
                ];
            }

            $tree[] = [
                'name' => $src->name,
                'value' => 'src:' . (string) $src->id,
                'disabled' => true,
                'children' => $children,
            ];
        }

        return $tree;
    }

    protected static function locationToSourceMap(): array
    {
        static $map = null;
        if ($map !== null) return $map;

        $map = StockSourceLocation::query()
            ->select(['id', 'stock_source_id'])
            ->pluck('stock_source_id', 'id')
            ->map(fn ($v) => (int) $v)
            ->all();

        return $map;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Підключення складу до магазину')
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

                            Hidden::make('stock_source_id'),

                            SelectTree::make('stock_source_location_id')
                                ->label('Склад (локація) *')
                                ->required()
                                ->multiple(false)
                                ->searchable()
                                ->showTags(false)
                                ->getTreeUsing(fn () => static::buildSourceLocationTree())
                                ->helperText('Вибирай саме локацію (листок). Джерело зверху — як група.')
                                ->afterStateHydrated(function ($state, $set, $get) {
                                    $state = filled($state) ? (string) $state : null;
                                    $set('stock_source_location_id', $state);

                                    $locId = ($state !== null && is_numeric($state)) ? (int) $state : null;
                                    if (! $locId) return;

                                    if (filled($get('stock_source_id'))) return;

                                    $map = static::locationToSourceMap();
                                    $set('stock_source_id', $map[$locId] ?? null);
                                })
                                ->afterStateUpdated(function ($state, $set) {
                                    $state = filled($state) ? (string) $state : null;
                                    $set('stock_source_location_id', $state);

                                    $locId = ($state !== null && is_numeric($state)) ? (int) $state : null;
                                    $map = static::locationToSourceMap();
                                    $set('stock_source_id', $locId ? ($map[$locId] ?? null) : null);
                                })
                                ->dehydrateStateUsing(fn ($state) => (filled($state) && is_numeric($state)) ? (int) $state : null)
                                ->rules([
                                    // ✅ Забороняємо дубль ДО збереження (нормальна помилка у формі)
                                    function ($get, $record) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                            $storeId = $get('store_id');
                                            $locId = $value;

                                            if (! filled($storeId) || ! filled($locId) || ! is_numeric($locId)) {
                                                return;
                                            }

                                            $q = StoreStockSource::query()
                                                ->where('store_id', (int) $storeId)
                                                ->where('stock_source_location_id', (int) $locId);

                                            // якщо редагуємо існуючий рядок — ігноруємо його
                                            if ($record?->id) {
                                                $q->where('id', '!=', $record->id);
                                            }

                                            if ($q->exists()) {
                                                $fail('Цю локацію вже підключено до цього магазину. Обери іншу.');
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
                                ->default(10)
                                ->helperText('Менше = важливіше. Напр.: 10/20/30...'),
                        ]),

                    Section::make('Додатково')
                        ->columnSpan(['default' => 1, 'lg' => 1])
                        ->schema([
                            Textarea::make('note')
                                ->label('Примітка')
                                ->rows(4)
                                ->maxLength(500),

                            KeyValue::make('settings')
                                ->label('settings')
                                ->default([])
                                ->helperText('Службові параметри (резерв/винятки/прапорці).'),
                        ]),
                ]),
            ]);
    }
}