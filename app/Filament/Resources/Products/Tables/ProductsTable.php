<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with([
                    'primaryImage:id,product_id,image_path,is_primary,sort_order',
                    'category:id,name_uk',
                    'manufacturer:id,name',
                    'bestSource:id,name',
                    'translations:product_id,locale,name',
                ]);
            })
            ->columns([
                ImageColumn::make('primaryImage.image_path')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->size(42)
                    // ✅ якщо немає фото — показуємо no_image.webp
                    ->defaultImageUrl(url('/images/no_image.webp'))
                    ->extraImgAttributes([
                        'loading' => 'lazy',
                        'style' => 'object-fit: contain; background: #fff;',
                    ])
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('display_name')
                    ->label('Назва')
                    ->state(fn ($record) => $record->display_name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $s = trim($search);

                        return $query->where(function (Builder $q) use ($s) {
                            $q->where('article_raw', 'like', "%{$s}%")
                                ->orWhere('article_norm', 'like', "%{$s}%")
                                ->orWhereHas('translations', fn (Builder $t) => $t->where('name', 'like', "%{$s}%"));
                        });
                    })
                    ->wrap()
                    ->sortable(),

                TextColumn::make('article_raw')
                    ->label('Артикул')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Артикул скопійовано')
                    ->toggleable(),

                TextColumn::make('manufacturer.name')
                    ->label('Виробник')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->toggleable(),

                TextColumn::make('category.name_uk')
                    ->label('Категорія')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->toggleable(),

                TextColumn::make('best_price_uah')
                    ->label('Краща ціна')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : (number_format((float) $state, 2, '.', ' ') . ' ₴'))
                    ->sortable(),

                TextColumn::make('best_stock_qty')
                    ->label('Доступно')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : rtrim(rtrim(number_format((float) $state, 3, '.', ' '), '0'), '.'))
                    ->sortable(),

                TextColumn::make('bestSource.name')
                    ->label('Джерело')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('best_delivery_days_min')
                    ->label('Доставка')
                    ->state(function ($record) {
                        $min = $record->best_delivery_days_min;
                        $max = $record->best_delivery_days_max;

                        if ($min === null && $max === null) return '—';
                        if ($min !== null && $max !== null) return "{$min}-{$max} дн.";
                        if ($min !== null) return "від {$min} дн.";
                        return "до {$max} дн.";
                    })
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Видалено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),

                Filter::make('in_stock_best')
                    ->label('В наявності (best)')
                    ->query(fn (Builder $q) => $q->whereNotNull('best_price_uah')->where('best_stock_qty', '>', 0)),

                Filter::make('no_best_price')
                    ->label('Без ціни')
                    ->query(fn (Builder $q) => $q->whereNull('best_price_uah')),

                Filter::make('no_stock')
                    ->label('Без залишку')
                    ->query(fn (Builder $q) => $q->whereNull('best_stock_qty')->orWhere('best_stock_qty', '<=', 0)),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                ])->iconButton(),
            ])

            // ✅ ЯК У КАТЕГОРІЯХ: кнопка “Відкрити дії” з’являється тільки коли вибрані записи
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ])->label('Відкрити дії'),
            ])

            // ⛔ прибираємо “крапки зверху”
            // ->toolbarActions([...])  НЕ потрібно
            ->defaultSort('updated_at', 'desc');
    }
}