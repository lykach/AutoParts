<?php

namespace App\Filament\Resources\PickupPointStoreStockSources\Tables;

use App\Models\DeliveryPickupPoint;
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
                Tables\Columns\TextColumn::make('pickup_view')
                    ->label('Точка самовивозу')
                    ->state(function ($record): string {
                        $pickup = $record->pickupPoint?->name_uk ?: ('#' . (int) $record->pickup_point_id);
                        $store = $record->pickupPoint?->store?->name_uk ?: ('#' . (int) ($record->pickupPoint?->store_id ?? 0));
                        return "{$pickup} [{$store}]";
                    })
                    ->wrap()
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('pickupPoint', fn ($q) => $q->where('name_uk', 'like', "%{$search}%"));
                    }),

                Tables\Columns\TextColumn::make('source_view')
                    ->label('Склад магазину')
                    ->state(function ($record): string {
                        $store = $record->storeStockSource?->store?->name_uk ?: ('#' . (int) ($record->storeStockSource?->store_id ?? 0));
                        $source = $record->storeStockSource?->stockSource?->name ?: ('#' . (int) ($record->storeStockSource?->stock_source_id ?? 0));
                        $location = $record->storeStockSource?->location?->name ?: ('#' . (int) ($record->storeStockSource?->stock_source_location_id ?? 0));
                        $city = $record->storeStockSource?->location?->city ? ' — ' . trim((string) $record->storeStockSource->location->city) : '';

                        return "{$store} / {$source} / {$location}{$city}";
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('eta_view')
                    ->label('Час довозу')
                    ->state(function ($record): string {
                        $unit = match ($record->transfer_time_unit) {
                            'minute' => 'хв',
                            'hour' => 'год',
                            'day' => 'дн',
                            default => $record->transfer_time_unit,
                        };

                        return "{$record->transfer_time_min}-{$record->transfer_time_max} {$unit}";
                    }),

                Tables\Columns\TextColumn::make('cutoff_at')
                    ->label('Cutoff')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Пріоритет')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('pickup_point_id')
                    ->label('Точка самовивозу')
                    ->searchable()
                    ->preload()
                    ->options(fn () => DeliveryPickupPoint::query()
                        ->orderBy('sort_order')
                        ->orderBy('name_uk')
                        ->pluck('name_uk', 'id')
                        ->all()
                    ),
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