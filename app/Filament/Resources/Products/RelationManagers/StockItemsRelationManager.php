<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Currency;
use App\Models\StockItem;
use App\Models\StockSourceLocation;
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
use Illuminate\Validation\Rules\Unique;

class StockItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockItems';
    protected static ?string $title = 'Залишки / Ціни';

    /**
     * ✅ Livewire state для create-модалки:
     * коли юзер вибрав склад, який вже існує для цього товару — тут збережемо id існуючого stock_item.
     */
    public ?int $existingStockItemId = null;

    public function form(Schema $schema): Schema
    {
        $owner = $this->getOwnerRecord(); // Product

        return $schema->components([
            // ✅ технічне поле (не пишемо в БД) — щоб можна було бачити state у Livewire девтулз, за бажання
            Hidden::make('existing_stock_item_id')
                ->dehydrated(false)
                ->default(null),

            Select::make('stock_source_location_id')
                ->label('Склад постачальника')
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->options(fn () => StockSourceLocation::query()
                    ->with('source')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(fn ($l) => [
                        $l->id => ($l->source?->name ? ($l->source->name . ' — ') : '') . $l->name,
                    ])
                    ->all()
                )
                // ✅ унікальність: (product_id + stock_source_location_id)
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
                ])
                ->afterStateUpdated(function ($state, callable $set, callable $get) use ($owner) {
                    // ✅ завжди скидаємо при зміні
                    $this->existingStockItemId = null;
                    $set('existing_stock_item_id', null);

                    if (!filled($state) || !($owner?->id)) {
                        return;
                    }

                    // ✅ CREATE-only (коли редагуємо — не треба)
                    $currentId = $get('id'); // на Edit буде id, на Create null
                    if (!filled($currentId)) {
                        $existingId = StockItem::query()
                            ->where('product_id', $owner->id)
                            ->where('stock_source_location_id', (int) $state)
                            ->value('id');

                        if ($existingId) {
                            $this->existingStockItemId = (int) $existingId;
                            $set('existing_stock_item_id', (int) $existingId);

                            Notification::make()
                                ->warning()
                                ->title('Склад уже доданий')
                                ->body('Можеш вибрати інший склад або натиснути “Відредагувати існуючий” внизу модалки.')
                                ->send();

                            // НЕ перекидаємо, НЕ закриваємо
                            return;
                        }
                    }

                    // ✅ підстановка допоміжних полів (якщо це НЕ дубль)
                    $loc = StockSourceLocation::query()
                        ->with('source:id,default_currency_code,delivery_unit,delivery_min,delivery_max')
                        ->whereKey($state)
                        ->first();

                    // stock_source_id завжди синхронізуємо з локації
                    if ($loc?->stock_source_id) {
                        $set('stock_source_id', (int) $loc->stock_source_id);
                    }

                    // валюта (якщо не вибрана)
                    if (!filled($get('currency'))) {
                        $set('currency', $loc?->source?->default_currency_code ?: 'UAH');
                    }

                    // доставка (підтягуємо, якщо ще не задана вручну)
                    if (!filled($get('delivery_unit')) && $get('delivery_min') === null && $get('delivery_max') === null) {
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
                ->minValue(0),

            TextInput::make('reserved_qty')
                ->label('Резерв')
                ->numeric()
                ->default(0)
                ->minValue(0),

            TextInput::make('multiplicity')
                ->label('Кратність')
                ->numeric()
                ->required()
                ->default(1)
                ->minValue(1),

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
                ->placeholder('—'),

            TextInput::make('price_sell')
                ->label('Продаж (в валюті)')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->placeholder('—'),

            TextInput::make('available_qty')
                ->label('Доступно (qty - резерв)')
                ->disabled()
                ->dehydrated(false),

            TextInput::make('sellable_qty')
                ->label('До продажу (з кратністю)')
                ->disabled()
                ->dehydrated(false),

            Select::make('delivery_unit')
                ->label('Доставка: одиниці')
                ->native(false)
                ->options(StockItem::deliveryUnitOptions())
                ->placeholder('Успадкувати'),

            TextInput::make('delivery_min')
                ->label('Доставка: від')
                ->numeric()
                ->minValue(0)
                ->placeholder('—'),

            TextInput::make('delivery_max')
                ->label('Доставка: до')
                ->numeric()
                ->minValue(0)
                ->placeholder('—'),
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

                Tables\Columns\TextColumn::make('qty')->label('Залишок')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('reserved_qty')->label('Резерв')->numeric()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('available_qty')->label('Доступно')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('sellable_qty')->label('До продажу')->numeric()->sortable(),

                Tables\Columns\TextColumn::make('price_sell')
                    ->label('Ціна')
                    ->formatStateUsing(function ($state, $record) {
                        if ($state === null) return '—';
                        $cur = strtoupper((string) ($record->currency ?? 'UAH'));
                        return number_format((float) $state, 2, '.', ' ') . " {$cur}";
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_sell_uah')
                    ->label('≈ UAH')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : number_format((float) $state, 2, '.', ' ') . ' UAH')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('delivery_min')
                    ->label('Доставка')
                    ->state(fn (StockItem $r) => $r->formatDelivery())
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
}