<?php

namespace App\Filament\Resources\Menus\Tables;

use App\Enums\MenuLocation;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MenusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')
                    ->label('Назва')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->code),

                TextColumn::make('location')
                    ->label('Розташування')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof MenuLocation ? $state->label() : ($state ? MenuLocation::from($state)->label() : '—'))
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Активне')
                    ->boolean(),

                TextColumn::make('items_count')
                    ->label('Пунктів')
                    ->counts('items'),

                TextColumn::make('sort')
                    ->label('Sort')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('location')
                    ->label('Розташування')
                    ->options(MenuLocation::options()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}