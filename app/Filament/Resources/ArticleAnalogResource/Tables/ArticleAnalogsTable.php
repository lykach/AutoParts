<?php

namespace App\Filament\Resources\ArticleAnalogResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ArticleAnalogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                BadgeColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn (?string $state) => $state === 'anti' ? 'Антикрос' : 'Крос')
                    ->colors([
                        'danger'  => 'anti',
                        'success' => 'cross',
                    ])
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Активний')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('manufacturer_article')
                    ->label('Виробник')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('article')
                    ->label('Артикул')
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(['class' => 'font-mono'])
                    ->copyable(),

                TextColumn::make('manufacturer_analog')
                    ->label('Виробник аналога')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('analog')
                    ->label('Аналог')
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(['class' => 'font-mono'])
                    ->copyable(),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'cross' => 'Кроси',
                        'anti'  => 'Антикроси',
                    ]),

                SelectFilter::make('is_active')
                    ->label('Активність')
                    ->options([
                        '1' => 'Активні',
                        '0' => 'Неактивні',
                    ]),
            ])
            ->recordActions([
                Action::make('toggleActive')
                    ->label(fn ($record) => $record->is_active ? 'Вимкнути' : 'Увімкнути')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn ($record) => $record->is_active ? 'gray' : 'success')
                    ->action(fn ($record) => $record->update(['is_active' => ! $record->is_active])),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
