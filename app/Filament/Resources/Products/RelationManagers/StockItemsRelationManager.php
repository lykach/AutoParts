<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Currency;
use App\Models\StockItem;
use App\Models\StockSourceLocation;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Unique;

class StockItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockItems';

    protected static ?string $title = 'Залишки / Ціни';

    public ?int $existingStockItemId = null;

    public function form(Schema $schema): Schema
    {
        $owner = $this->getOwnerRecord();

        return $schema->components([
            Hidden::make('existing_stock_item_id')
                ->dehydrated(false)
                ->default(null),

            SelectTree::make('stock_source_location_id')
                ->label('Склад постачальника')
                ->required()
                ->getTreeUsing(fn () => $this->buildStockSourceLocationTree())
                ->searchable()
                ->defaultOpenLevel(1)
                ->placeholder('Оберіть склад постачальника')
                ->emptyLabel('Нічого не знайдено')
                ->helperText('Склади згруповані по постачальниках. Вибирається тільки конкретний склад.')
                ->live()
                ->multiple(false)
                ->validationAttribute('склад постачальника')
                ->unique(
                    table: StockItem::class,
                    column: 'stock_source_location_id',
                    ignoreRecord: true,
                    modifyRuleUsing: function (Unique $rule) use ($owner) {
                        if ($owner?->id) {
                            $rule->where('product_id', $owner->id);
                        }

                        return $rule;
                    }
                )
                ->validationMessages([
                    'unique' => 'Для цього товару цей склад уже доданий. Можеш вибрати інший або натиснути “Відредагувати існуючий”.',
                    'required' => 'Оберіть склад постачальника.',
                ])
                ->afterStateUpdated(function ($state, callable $set, callable $get) use ($owner) {
                    $this->existingStockItemId = null;
                    $set('existing_stock_item_id', null);

                    if (! filled($state) || ! ($owner?->id)) {
                        return;
                    }

                    $locationId = (int) $state;

                    if ($locationId <= 0) {
                        return;
                    }

                    $currentId = $get('id');

                    if (! filled($currentId)) {
                        $existingId = StockItem::query()
                            ->where('product_id', $owner->id)
                            ->where('stock_source_location_id', $locationId)
                            ->value('id');

                        if ($existingId) {
                            $this->existingStockItemId = (int) $existingId;
                            $set('existing_stock_item_id', (int) $existingId);

                            Notification::make()
                                ->warning()
                                ->title('Склад уже доданий')
                                ->body('Можеш вибрати інший склад або натиснути “Відредагувати існуючий” внизу модалки.')
                                ->send();

                            return;
                        }
                    }

                    $loc = StockSourceLocation::query()
                        ->with('source:id,name,default_currency_code,delivery_unit,delivery_min,delivery_max')
                        ->whereKey($locationId)
                        ->first();

                    if ($loc?->stock_source_id) {
                        $set('stock_source_id', (int) $loc->stock_source_id);
                    }

                    if (! filled($get('currency'))) {
                        $set('currency', $loc?->source?->default_currency_code ?: 'UAH');
                    }

                    if (
                        ! filled($get('delivery_unit'))
                        && $get('delivery_min') === null
                        && $get('delivery_max') === null
                    ) {
                        $unit = $loc?->delivery_unit ?: ($loc?->source?->delivery_unit ?: 'days');

                        $set('delivery_unit', $unit);
                        $set('delivery_min', $loc?->delivery_min ?? $loc?->source?->delivery_min);
                        $set('delivery_max', $loc?->delivery_max ?? $loc?->source?->delivery_max);
                    }
                }),

            Hidden::make('stock_source_id'),

            Select::make('availability_status')
                ->label('Статус')
                ->required()
                ->default('in_stock')
                ->options(StockItem::availabilityOptions())
                ->native(false),

            TextInput::make('qty')
                ->label('Залишок')
                ->numeric()
                ->required()
                ->default(0)
                ->minValue(0)
                ->live(debounce: 300)
                ->dehydrateStateUsing(fn ($state) => self::normalizeNumberToZero($state))
                ->afterStateHydrated(function ($state, callable $set) {
                    $set('qty', self::normalizeNumberToZero($state));
                })
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    self::refreshComputedQuantities($set, $get);
                }),

            TextInput::make('reserved_qty')
                ->label('Резерв')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->live(debounce: 300)
                ->dehydrateStateUsing(fn ($state) => self::normalizeNumberToZero($state))
                ->afterStateHydrated(function ($state, callable $set) {
                    $set('reserved_qty', self::normalizeNumberToZero($state));
                })
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    self::refreshComputedQuantities($set, $get);
                }),

            TextInput::make('multiplicity')
                ->label('Кратність')
                ->numeric()
                ->required()
                ->default(1)
                ->minValue(1)
                ->live(debounce: 300)
                ->dehydrateStateUsing(fn ($state) => self::normalizeNumberToOne($state))
                ->afterStateHydrated(function ($state, callable $set) {
                    $set('multiplicity', self::normalizeNumberToOne($state));
                })
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    self::refreshComputedQuantities($set, $get);
                }),

            Select::make('currency')
                ->label('Валюта')
                ->required()
                ->native(false)
                ->options(fn () => Currency::query()
                    ->where('is_active', true)
                    ->orderByDesc('is_default')
                    ->orderBy('code')
                    ->pluck('code', 'code')
                    ->all()
                )
                ->default('UAH'),

            TextInput::make('price_purchase')
                ->label('Закупка (в валюті)')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->placeholder('—')
                ->dehydrateStateUsing(fn ($state) => self::normalizeNullableDecimal($state)),

            TextInput::make('price_sell')
                ->label('Продаж (в валюті)')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->placeholder('—')
                ->dehydrateStateUsing(fn ($state) => self::normalizeNullableDecimal($state)),

            TextInput::make('available_qty')
                ->label('Доступно (qty - резерв)')
                ->disabled()
                ->dehydrated(false)
                ->formatStateUsing(fn ($state) => $state !== null ? (string) $state : null)
                ->afterStateHydrated(function ($state, callable $set, callable $get) {
                    self::refreshComputedQuantities($set, $get);
                }),

            TextInput::make('sellable_qty')
                ->label('До продажу (з кратністю)')
                ->disabled()
                ->dehydrated(false)
                ->formatStateUsing(fn ($state) => $state !== null ? (string) $state : null)
                ->afterStateHydrated(function ($state, callable $set, callable $get) {
                    self::refreshComputedQuantities($set, $get);
                }),

            Select::make('delivery_unit')
                ->label('Доставка: одиниці')
                ->native(false)
                ->options(StockItem::deliveryUnitOptions())
                ->placeholder('Успадкувати'),

            TextInput::make('delivery_min')
                ->label('Доставка: від')
                ->numeric()
                ->minValue(0)
                ->placeholder('—')
                ->dehydrateStateUsing(fn ($state) => self::normalizeNullableInt($state)),

            TextInput::make('delivery_max')
                ->label('Доставка: до')
                ->numeric()
                ->minValue(0)
                ->placeholder('—')
                ->dehydrateStateUsing(fn ($state) => self::normalizeNullableInt($state)),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['location.source']))
            ->columns([
                Tables\Columns\TextColumn::make('location.source.name')
                    ->label('Постачальник')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('location.name')
                    ->label('Склад')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('availability_status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => StockItem::availabilityOptions()[$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Залишок')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reserved_qty')
                    ->label('Резерв')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('available_qty')
                    ->label('Доступно')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sellable_qty')
                    ->label('До продажу')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_sell')
                    ->label('Ціна')
                    ->formatStateUsing(function ($state, $record) {
                        if ($state === null) {
                            return '—';
                        }

                        $cur = strtoupper((string) ($record->currency ?? 'UAH'));

                        return number_format((float) $state, 2, '.', ' ') . " {$cur}";
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_sell_uah')
                    ->label('≈ UAH')
                    ->formatStateUsing(
                        fn ($state) => $state === null
                            ? '—'
                            : number_format((float) $state, 2, '.', ' ') . ' UAH'
                    )
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('delivery_min')
                    ->label('Доставка')
                    ->state(fn (StockItem $record) => $record->formatDelivery())
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Додати')
                    ->extraModalFooterActions([
                        Action::make('editExistingFromCreate')
                            ->label('Відредагувати існуючий')
                            ->icon('heroicon-o-pencil-square')
                            ->color('warning')
                            ->visible(fn () => filled($this->existingStockItemId))
                            ->action(function () {
                                $id = (int) ($this->existingStockItemId ?? 0);

                                if ($id <= 0) {
                                    return;
                                }

                                $this->unmountTableAction();
                                $this->mountTableAction('edit', $id);
                            }),
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    protected function buildStockSourceLocationTree(): array
    {
        $locations = StockSourceLocation::query()
            ->with('source:id,name')
            ->orderBy('stock_source_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        /** @var Collection<int, Collection<int, StockSourceLocation>> $grouped */
        $grouped = $locations->groupBy(fn (StockSourceLocation $location) => $location->stock_source_id ?: 0);

        return $grouped
            ->map(function (Collection $sourceLocations, $sourceId) {
                /** @var StockSourceLocation|null $first */
                $first = $sourceLocations->first();

                $sourceName = $first?->source?->name ?: 'Без постачальника';

                return [
                    'name' => sprintf('🏢 %s (%d)', $sourceName, $sourceLocations->count()),
                    'value' => 'source-' . $sourceId,
                    'disabled' => true,
                    'children' => $sourceLocations
                        ->map(function (StockSourceLocation $location) {
                            return [
                                'name' => $this->formatLocationNodeLabel($location),
                                'value' => (string) $location->id,
                                'disabled' => false,
                                'children' => [],
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    protected function formatLocationNodeLabel(StockSourceLocation $location): string
    {
        $parts = [];

        $parts[] = '📦 ' . $location->name;

        if (filled($location->code ?? null)) {
            $parts[] = '[' . $location->code . ']';
        }

        if (filled($location->city ?? null)) {
            $parts[] = '— ' . $location->city;
        }

        return implode(' ', $parts);
    }

    protected static function normalizeNumberToZero(mixed $state): int|float
    {
        if ($state === null || $state === '') {
            return 0;
        }

        return is_numeric($state) ? (float) $state : 0;
    }

    protected static function normalizeNumberToOne(mixed $state): int|float
    {
        if ($state === null || $state === '') {
            return 1;
        }

        $value = is_numeric($state) ? (float) $state : 1;

        return $value < 1 ? 1 : $value;
    }

    protected static function normalizeNullableDecimal(mixed $state): int|float|null
    {
        if ($state === null || $state === '') {
            return null;
        }

        return is_numeric($state) ? (float) $state : null;
    }

    protected static function normalizeNullableInt(mixed $state): ?int
    {
        if ($state === null || $state === '') {
            return null;
        }

        return is_numeric($state) ? (int) $state : null;
    }

    protected static function refreshComputedQuantities(callable $set, callable $get): void
    {
        $qty = (float) self::normalizeNumberToZero($get('qty'));
        $reserved = (float) self::normalizeNumberToZero($get('reserved_qty'));
        $multiplicity = (float) self::normalizeNumberToOne($get('multiplicity'));

        $available = max($qty - $reserved, 0);
        $sellable = $multiplicity > 0
            ? floor($available / $multiplicity) * $multiplicity
            : $available;

        $set('available_qty', number_format($available, 3, '.', ''));
        $set('sellable_qty', number_format($sellable, 3, '.', ''));
    }
}