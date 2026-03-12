<?php

namespace App\Filament\Resources\DeliveryMethods\Tables;

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

class DeliveryMethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_uk')
                    ->label('Назва')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): string => $record->code),

                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pickup' => 'Самовивіз',
                        'carrier' => 'Служба доставки',
                        'courier' => 'Курʼєр',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Сортування')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('store_links_count')
                    ->label('Магазинів')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'pickup' => 'Самовивіз',
                        'carrier' => 'Служба доставки',
                        'courier' => 'Курʼєр',
                    ]),

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
                                ->body('Вибрані способи доставки активовано.')
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
                                ->body('Вибрані способи доставки деактивовано.')
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
            ->emptyStateHeading('Способів доставки ще немає')
            ->emptyStateDescription('Натисни “Створити спосіб доставки”, щоб додати перший запис.');
    }
}