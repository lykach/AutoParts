<?php

namespace App\Filament\Resources\PickupPointStoreStockSources\Tables;

use App\Models\DeliveryPickupPoint;
use App\Models\Store;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class PickupPointStoreStockSourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pickup_point_view')
                    ->label('Точка самовивозу')
                    ->state(function ($record): string {
                        return $record->pickupPoint?->name
                            ?: ('#' . (int) $record->pickup_point_id);
                    })
                    ->description(function ($record): string {
                        $storeName = $record->pickupPoint?->store?->name_uk
                            ?: ('#' . (int) ($record->pickupPoint?->store_id ?? 0));

                        $storeType = $record->pickupPoint?->store?->is_main ? 'Головний' : 'Філія';

                        return "{$storeName} ({$storeType})";
                    })
                    ->wrap()
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('pickupPoint', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                                ->orWhereHas('store', function ($sq) use ($search) {
                                    $sq->where('name_uk', 'like', "%{$search}%");
                                });
                        });
                    }),

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
                    ->wrap()
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('storeStockSource', function ($q) use ($search) {
                            $q->whereHas('store', fn ($sq) => $sq->where('name_uk', 'like', "%{$search}%"))
                                ->orWhereHas('stockSource', fn ($sq) => $sq->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('location', function ($sq) use ($search) {
                                    $sq->where('name', 'like', "%{$search}%")
                                        ->orWhere('city', 'like', "%{$search}%");
                                });
                        });
                    }),

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
                    ->state(fn ($record) => $record->cutoff_at ?: '—')
                    ->sortable(),

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
            ->filters([
                SelectFilter::make('pickup_point_id')
                    ->label('Точка самовивозу')
                    ->searchable()
                    ->preload()
                    ->options(fn () => DeliveryPickupPoint::query()
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()
                    ),

                SelectFilter::make('pickup_store_id')
                    ->label('Магазин точки')
                    ->searchable()
                    ->preload()
                    ->options(fn () => Store::query()
                        ->orderByDesc('is_main')
                        ->orderBy('sort_order')
                        ->pluck('name_uk', 'id')
                        ->all()
                    )
                    ->query(function ($query, array $data) {
                        if (! filled($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->whereHas('pickupPoint', function ($q) use ($data) {
                            $q->where('store_id', (int) $data['value']);
                        });
                    }),

                SelectFilter::make('source_store_id')
                    ->label('Магазин складу')
                    ->searchable()
                    ->preload()
                    ->options(fn () => Store::query()
                        ->orderByDesc('is_main')
                        ->orderBy('sort_order')
                        ->pluck('name_uk', 'id')
                        ->all()
                    )
                    ->query(function ($query, array $data) {
                        if (! filled($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->whereHas('storeStockSource', function ($q) use ($data) {
                            $q->where('store_id', (int) $data['value']);
                        });
                    }),

                SelectFilter::make('is_active')
                    ->label('Статус')
                    ->options([
                        '1' => 'Активні',
                        '0' => 'Неактивні',
                    ])
                    ->query(function ($query, array $data) {
                        if (! array_key_exists('value', $data) || $data['value'] === null || $data['value'] === '') {
                            return $query;
                        }

                        return $query->where('is_active', (bool) $data['value']);
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('setActive')
                        ->label('Зробити активними')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->is_active = true;
                                $record->save();
                            });

                            Notification::make()
                                ->title('Готово')
                                ->body('Вибрані джерела активовано.')
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('setInactive')
                        ->label('Зробити неактивними')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->is_active = false;
                                $record->save();
                            });

                            Notification::make()
                                ->title('Готово')
                                ->body('Вибрані джерела деактивовано.')
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->label('Видалити вибране')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation(),
                ])
                    ->label('Відкрити дії')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->defaultSort('priority', 'asc')
            ->emptyStateHeading('Джерел самовивозу ще немає')
            ->emptyStateDescription('Натисни “Підключити джерело”, щоб додати перший запис.');
    }
}