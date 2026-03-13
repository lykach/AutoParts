<?php

namespace App\Filament\Resources\CityCourierZoneSlots\Tables;

use App\Models\CityCourierZone;
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

class CityCourierZoneSlotsTable
{
    protected static function formatDays(?array $days): string
    {
        $map = [
            'mon' => 'Пн',
            'tue' => 'Вт',
            'wed' => 'Ср',
            'thu' => 'Чт',
            'fri' => 'Пт',
            'sat' => 'Сб',
            'sun' => 'Нд',
        ];

        $days = is_array($days) ? $days : [];

        if ($days === []) {
            return '—';
        }

        return implode(', ', array_map(fn ($day) => $map[$day] ?? $day, $days));
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('zone_view')
                    ->label('Зона')
                    ->state(function ($record): string {
                        return $record->zone?->name_uk ?: ('#' . (int) $record->city_courier_zone_id);
                    })
                    ->description(function ($record): string {
                        $store = $record->zone?->store?->name_uk ?: ('#' . (int) ($record->zone?->store_id ?? 0));
                        $city = $record->zone?->city_uk ?: '—';

                        return "{$store} / {$city}";
                    })
                    ->wrap()
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('zone', function ($q) use ($search) {
                            $q->where('name_uk', 'like', "%{$search}%")
                                ->orWhere('city_uk', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->label('Слот')
                    ->state(fn ($record) => $record->name ?: 'Без назви')
                    ->wrap(),

                Tables\Columns\TextColumn::make('time_range')
                    ->label('Час доставки')
                    ->state(fn ($record) => "{$record->delivery_time_from}–{$record->delivery_time_to}")
                    ->badge(),

                Tables\Columns\TextColumn::make('work_days')
                    ->label('Дні')
                    ->state(fn ($record) => static::formatDays($record->work_days))
                    ->wrap(),

                Tables\Columns\IconColumn::make('same_day_enabled')
                    ->label('Same day')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('same_day_cutoff_at')
                    ->label('Cutoff')
                    ->state(fn ($record) => $record->same_day_cutoff_at ?: '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('exceptions_count')
                    ->label('Винятків')
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
                SelectFilter::make('city_courier_zone_id')
                    ->label('Зона')
                    ->searchable()
                    ->preload()
                    ->options(fn () => CityCourierZone::query()
                        ->orderBy('sort_order')
                        ->orderBy('name_uk')
                        ->pluck('name_uk', 'id')
                        ->all()
                    ),

                SelectFilter::make('same_day_enabled')
                    ->label('Same day')
                    ->options([
                        '1' => 'Так',
                        '0' => 'Ні',
                    ])
                    ->query(function ($query, array $data) {
                        if (($data['value'] ?? '') === '') {
                            return $query;
                        }

                        return $query->where('same_day_enabled', (bool) $data['value']);
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
                                ->body('Вибрані слоти активовано.')
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
                                ->body('Вибрані слоти деактивовано.')
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
            ->emptyStateHeading('Слотів доставки ще немає')
            ->emptyStateDescription('Натисни “Створити слот”, щоб додати перший.');
    }
}