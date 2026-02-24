<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Currency;
use App\Models\StockItem;
use App\Models\StockSourceLocation;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockItems';

    protected static ?string $title = 'Залишки / Ціни';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
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
                    ->mapWithKeys(fn ($l) => [$l->id => ($l->source?->name ? ($l->source->name . ' — ') : '') . $l->name])
                    ->all()
                )
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    // ✅ якщо валюта не вибрана — підставимо дефолт валюти джерела
                    $currentCur = $get('currency');
                    if (!filled($currentCur) && filled($state)) {
                        $def = StockSourceLocation::query()
                            ->with('source:id,default_currency_code')
                            ->whereKey($state)
                            ->first()?->source?->default_currency_code ?: 'UAH';

                        $set('currency', $def);
                    }

                    // ✅ автоматично проставимо stock_source_id для консистентності/швидких фільтрів
                    if (filled($state)) {
                        $sid = StockSourceLocation::query()->whereKey($state)->value('stock_source_id');
                        if ($sid) $set('stock_source_id', (int) $sid);
                    }
                }),

            // приховане поле, але зберігаємо в БД для швидких запитів
            TextInput::make('stock_source_id')
                ->dehydrated(true)
                ->disabled()
                ->hidden(),

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
                ->minValue(0)
                ->helperText('Зараз можна редагувати вручну. Пізніше буде автоматично з замовлень.'),

            TextInput::make('multiplicity')
                ->label('Кратність (multiplicity)')
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
                ->default(fn () => 'UAH'),

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
                ->dehydrated(false)
                ->formatStateUsing(fn ($state, $record) => $record ? $record->available_qty : null),

            TextInput::make('available_for_sale_qty')
                ->label('Доступно до продажу (з multiplicity)')
                ->disabled()
                ->dehydrated(false)
                ->formatStateUsing(fn ($state, $record) => $record ? $record->available_for_sale_qty : null),

            TextInput::make('delivery_days_min')
                ->label('Доставка від (днів)')
                ->numeric()
                ->minValue(0)
                ->placeholder('—'),

            TextInput::make('delivery_days_max')
                ->label('до (днів)')
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
                    ->state(fn ($record) => $record->available_qty)
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('multiplicity')
                    ->label('Multiplicity')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('available_for_sale_qty')
                    ->label('До продажу')
                    ->state(fn ($record) => $record->available_for_sale_qty)
                    ->numeric()
                    ->sortable(),

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
                    ->state(fn ($record) => $record->price_sell_uah)
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : number_format((float) $state, 2, '.', ' ') . ' UAH')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('delivery_days_min')
                    ->label('Доставка')
                    ->formatStateUsing(function ($state, $record) {
                        $min = $record->delivery_days_min;
                        $max = $record->delivery_days_max;

                        if ($min === null && $max === null) return '—';
                        if ($min !== null && $max !== null) return "{$min}-{$max} дн.";
                        if ($min !== null) return "від {$min} дн.";
                        return "до {$max} дн.";
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()->label('Додати'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }
}