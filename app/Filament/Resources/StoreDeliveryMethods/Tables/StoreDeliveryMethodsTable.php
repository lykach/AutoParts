<?php

namespace App\Filament\Resources\StoreDeliveryMethods\Tables;

use App\Models\DeliveryMethod;
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

class StoreDeliveryMethodsTable
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

                Tables\Columns\TextColumn::make('delivery_view')
                    ->label('Спосіб доставки')
                    ->state(function ($record): string {
                        $name = $record->deliveryMethod?->name_uk ?: ('#' . (int) $record->delivery_method_id);
                        $code = $record->deliveryMethod?->code;

                        return $code ? "{$name} ({$code})" : $name;
                    })
                    ->wrap()
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('deliveryMethod', function ($q) use ($search) {
                            $q->where('name_uk', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('deliveryMethod.type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pickup' => 'Самовивіз',
                        'carrier' => 'Служба доставки',
                        'courier' => 'Курʼєр',
                        default => $state ?? '—',
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

                SelectFilter::make('delivery_method_id')
                    ->label('Спосіб доставки')
                    ->searchable()
                    ->preload()
                    ->options(fn () => DeliveryMethod::query()
                        ->orderBy('sort_order')
                        ->orderBy('name_uk')
                        ->pluck('name_uk', 'id')
                        ->all()
                    ),

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
                                ->body('Вибрані привʼязки активовано.')
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
                                ->body('Вибрані привʼязки деактивовано.')
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
            ->emptyStateHeading('Привʼязок доставок до магазинів ще немає')
            ->emptyStateDescription('Натисни “Підключити доставку”, щоб додати перший запис.');
    }
}