<?php

namespace App\Filament\Resources\StoreStockSources\Tables;

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

class StoreStockSourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store_view')
                    ->label('Магазин')
                    ->state(function ($record): string {
                        $name = $record->store?->name_uk ?: ('#' . (int) $record->store_id);
                        $type = $record->store?->is_main ? 'Головний' : 'Філія';
                        return "{$name} ({$type})";
                    })
                    ->wrap()
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('store', fn ($q) => $q->where('name_uk', 'like', "%{$search}%"));
                    }),

                Tables\Columns\TextColumn::make('source_view')
                    ->label('Джерело')
                    ->state(fn ($record) => $record->stockSource?->name ?: ('#' . (int) $record->stock_source_id))
                    ->wrap()
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('stockSource', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                    }),

                Tables\Columns\TextColumn::make('location_view')
                    ->label('Локація')
                    ->state(function ($record): string {
                        $name = $record->location?->name ?: ('#' . (int) $record->stock_source_location_id);
                        $city = $record->location?->city ? trim((string) $record->location->city) : null;
                        return $city ? "{$name} — {$city}" : $name;
                    })
                    ->wrap()
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('location', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('city', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Пріоритет')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('store_id')
                    ->label('Магазин')
                    ->searchable()
                    ->preload()
                    ->options(fn () => Store::query()
                        ->orderByDesc('is_main')
                        ->orderBy('sort_order')
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
                                ->body('Вибрані записи зроблено активними.')
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
                                ->body('Вибрані записи зроблено неактивними.')
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
            ->emptyStateHeading('Підключених складів ще немає')
            ->emptyStateDescription('Натисни “Підключити склад”, щоб додати локацію до магазину.');
    }
}