<?php

namespace App\Filament\Resources\CityCourierSlotExceptions\Tables;

use App\Models\CityCourierZoneSlot;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CityCourierSlotExceptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slot_view')
                    ->label('Слот')
                    ->state(function ($record): string {
                        $zone = $record->slot?->zone?->name_uk ?: ('#' . (int) ($record->slot?->city_courier_zone_id ?? 0));
                        $slotName = $record->slot?->name ?: (($record->slot?->delivery_time_from ?? '—') . '–' . ($record->slot?->delivery_time_to ?? '—'));

                        return "{$zone} / {$slotName}";
                    })
                    ->description(function ($record): string {
                        $store = $record->slot?->zone?->store?->name_uk ?: ('#' . (int) ($record->slot?->zone?->store_id ?? 0));
                        $city = $record->slot?->zone?->city_uk ?: '—';

                        return "{$store} / {$city}";
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('exception_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_closed')
                    ->label('Закрито')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('time_override')
                    ->label('Override часу')
                    ->state(function ($record): string {
                        $from = $record->override_delivery_time_from;
                        $to = $record->override_delivery_time_to;

                        if (! $from && ! $to) {
                            return '—';
                        }

                        return ($from ?: '—') . '–' . ($to ?: '—');
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('override_price')
                    ->label('Override ціни')
                    ->state(fn ($record) => filled($record->override_price) ? number_format((float) $record->override_price, 2, '.', ' ') . ' ₴' : '—'),

                Tables\Columns\TextColumn::make('override_eta')
                    ->label('Override ETA')
                    ->state(function ($record): string {
                        $min = $record->override_eta_min_minutes;
                        $max = $record->override_eta_max_minutes;

                        if (! filled($min) && ! filled($max)) {
                            return '—';
                        }

                        if (filled($min) && filled($max)) {
                            return $min == $max ? "{$min} хв" : "{$min}–{$max} хв";
                        }

                        return (string) ($min ?? $max) . ' хв';
                    }),

                Tables\Columns\TextColumn::make('max_orders')
                    ->label('Ліміт')
                    ->state(fn ($record) => filled($record->max_orders) ? (string) $record->max_orders : '—'),
            ])
            ->filters([
                SelectFilter::make('city_courier_zone_slot_id')
                    ->label('Слот')
                    ->searchable()
                    ->preload()
                    ->options(fn () => CityCourierZoneSlot::query()
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->get()
                        ->mapWithKeys(fn ($row) => [
                            $row->id => ($row->name ?: (($row->delivery_time_from ?? '—') . '–' . ($row->delivery_time_to ?? '—'))),
                        ])
                        ->all()
                    ),

                SelectFilter::make('is_closed')
                    ->label('Закрито')
                    ->options([
                        '1' => 'Так',
                        '0' => 'Ні',
                    ])
                    ->query(function ($query, array $data) {
                        if (($data['value'] ?? '') === '') {
                            return $query;
                        }

                        return $query->where('is_closed', (bool) $data['value']);
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Видалити вибране')
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('exception_date', 'desc')
            ->emptyStateHeading('Винятків ще немає')
            ->emptyStateDescription('Натисни “Створити виняток”, щоб додати перший.');
    }
}