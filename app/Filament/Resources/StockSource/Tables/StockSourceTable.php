<?php

namespace App\Filament\Resources\StockSource\Tables;

use App\Rules\UkrainianPhone;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockSourceTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->withCount('storeLinks');
            })
            ->columns([
                IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Назва')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Код скопійовано')
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Телефон скопійовано')
                    ->icon('heroicon-o-phone')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => UkrainianPhone::format($state))
                    ->toggleable(),

                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->sortable(),

                TextColumn::make('city')
                    ->label('Місто')
                    ->toggleable(),

                TextColumn::make('store_links_count')
                    ->label('Магазинів')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('active')
                    ->label('Тільки активні')
                    ->query(fn (Builder $q) => $q->where('is_active', true)),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),

                    // ✅ не даємо видалити, якщо є привʼязки
                    DeleteAction::make()
                        ->disabled(fn ($record) => (int) ($record->store_links_count ?? 0) > 0)
                        ->tooltip(fn ($record) => ((int) ($record->store_links_count ?? 0) > 0)
                            ? 'Неможливо видалити: джерело використовується в магазинах.'
                            : null
                        ),
                ])->iconButton(),
            ])
            ->defaultSort('sort_order', 'asc');
    }
}
