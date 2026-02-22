<?php

namespace App\Filament\Resources\CharacteristicsProduct\Tables;

use App\Models\CharacteristicsProduct;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CharacteristicsProductTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // ✅ щоб не було N+1 і ми бачили count
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('values'))
            ->columns([
                TextColumn::make('sort')
                    ->label('Порядок')
                    ->sortable()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge(),

                TextColumn::make('group_uk')
                    ->label('Група')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('name_uk')
                    ->label('Назва (UK)')
                    ->searchable()
                    ->sortable(),

                // ✅ ЛІЧИЛЬНИК: скільки значень у словнику
                TextColumn::make('values_count')
                    ->label('Значень')
                    ->badge()
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => (string) ($state ?? 0)),

                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'text'   => 'Текст',
                        'number' => 'Число',
                        'bool'   => 'Так/Ні',
                        'select' => 'Список',
                        default  => (string) $state,
                    }),

                TextColumn::make('unit')
                    ->label('Од.')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                IconColumn::make('is_important')
                    ->label('ТОП')
                    ->boolean()
                    ->sortable()
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('is_filterable')
                    ->label('Фільтр')
                    ->boolean()
                    ->sortable()
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('is_visible')
                    ->label('Видимість')
                    ->boolean()
                    ->sortable()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->defaultSort('sort', 'asc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'text'   => 'Текст',
                        'number' => 'Число',
                        'bool'   => 'Так/Ні',
                        'select' => 'Список',
                    ])
                    ->placeholder('Всі типи'),

                TernaryFilter::make('is_visible')
                    ->label('Видимість')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки видимі')
                    ->falseLabel('Тільки приховані'),

                TernaryFilter::make('is_filterable')
                    ->label('Фільтр')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки фільтруємі')
                    ->falseLabel('Тільки нефільтруємі'),

                TernaryFilter::make('is_important')
                    ->label('ТОП')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки ТОП')
                    ->falseLabel('Без ТОП'),
            ])
            ->recordUrl(fn (CharacteristicsProduct $record): string =>
                route('filament.admin.resources.characteristics-product.characteristics-products.edit', ['record' => $record])
            )
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->paginated([50, 100, 'all'])
            ->defaultPaginationPageOption(50);
    }
}