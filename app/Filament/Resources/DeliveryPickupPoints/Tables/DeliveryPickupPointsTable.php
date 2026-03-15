<?php

namespace App\Filament\Resources\DeliveryPickupPoints\Tables;

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

class DeliveryPickupPointsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Точка самовивозу')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn ($record): string => $record->code),

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

                Tables\Columns\TextColumn::make('resolved_phone')
                    ->label('Телефон')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('resolved_address')
                    ->label('Адреса')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sources_view')
                    ->label('Джерела')
                    ->state(function ($record): string {
                        $all = (int) ($record->stock_source_links_count ?? 0);
                        $active = (int) ($record->active_stock_source_links_count ?? 0);

                        return "{$active} / {$all}";
                    })
                    ->badge()
                    ->color(function ($record): string {
                        $all = (int) ($record->stock_source_links_count ?? 0);
                        $active = (int) ($record->active_stock_source_links_count ?? 0);

                        if ($all === 0) {
                            return 'danger';
                        }

                        if ($active === 0) {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->sortable(query: function ($query, string $direction) {
                        return $query
                            ->orderBy('active_stock_source_links_count', $direction)
                            ->orderBy('stock_source_links_count', $direction);
                    })
                    ->tooltip(function ($record): string {
                        $all = (int) ($record->stock_source_links_count ?? 0);
                        $active = (int) ($record->active_stock_source_links_count ?? 0);

                        return "Активних: {$active}, всього: {$all}";
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Сортування')
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
                                ->body('Вибрані точки активовано.')
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
                                ->body('Вибрані точки деактивовано.')
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
            ->defaultSort('sort_order', 'asc')
            ->emptyStateHeading('Точок самовивозу ще немає')
            ->emptyStateDescription('Натисни “Створити точку самовивозу”, щоб додати першу.');
    }
}