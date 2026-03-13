<?php

namespace App\Filament\Resources\CityCourierZones\Tables;

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

class CityCourierZonesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_uk')
                    ->label('Зона')
                    ->searchable()
                    ->sortable()
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

                Tables\Columns\TextColumn::make('city_uk')
                    ->label('Місто')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('delivery_price')
                    ->label('Ціна')
                    ->money('UAH')
                    ->sortable(),

                Tables\Columns\TextColumn::make('free_from_amount')
                    ->label('Безкоштовно від')
                    ->state(fn ($record) => filled($record->free_from_amount) ? number_format((float) $record->free_from_amount, 2, '.', ' ') . ' ₴' : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('eta_view')
                    ->label('ETA')
                    ->state(function ($record): string {
                        $min = (int) $record->eta_min_minutes;
                        $max = (int) $record->eta_max_minutes;

                        return $min === $max ? "{$min} хв" : "{$min}–{$max} хв";
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('payment_view')
                    ->label('Оплата')
                    ->state(function ($record): string {
                        $parts = [];

                        if ($record->cash_allowed) {
                            $parts[] = 'Готівка';
                        }

                        if ($record->card_allowed) {
                            $parts[] = 'Картка';
                        }

                        if ($record->cod_allowed) {
                            $parts[] = 'Післяплата';
                        }

                        return $parts ? implode(', ', $parts) : '—';
                    })
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('slots_count')
                    ->label('Слотів')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Сортування')
                    ->numeric()
                    ->sortable(),
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

                SelectFilter::make('cod_allowed')
                    ->label('Післяплата')
                    ->options([
                        '1' => 'Так',
                        '0' => 'Ні',
                    ])
                    ->query(function ($query, array $data) {
                        if (($data['value'] ?? '') === '') {
                            return $query;
                        }

                        return $query->where('cod_allowed', (bool) $data['value']);
                    }),

                SelectFilter::make('is_active')
                    ->label('Статус')
                    ->options([
                        '1' => 'Активні',
                        '0' => 'Неактивні',
                    ])
                    ->query(function ($query, array $data) {
                        if (($data['value'] ?? '') === '') {
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
                                ->body('Вибрані зони активовано.')
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
                                ->body('Вибрані зони деактивовано.')
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
            ->emptyStateHeading('Зон курʼєрської доставки ще немає')
            ->emptyStateDescription('Натисни “Створити зону”, щоб додати першу.');
    }
}