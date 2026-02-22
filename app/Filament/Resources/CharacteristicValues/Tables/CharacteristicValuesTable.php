<?php

namespace App\Filament\Resources\CharacteristicValues\Tables;

use App\Models\CharacteristicValue;
use App\Models\CharacteristicsProduct;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CharacteristicValuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('characteristic'))
            ->columns([
                TextColumn::make('id')->label('#')->sortable()->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('characteristic.name_uk')
                    ->label('Характеристика')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('value_key')
                    ->label('Ключ')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->toggleable(),

                TextColumn::make('value_uk')
                    ->label('Значення (UK)')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('value_number')
                    ->label('Число')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                IconColumn::make('value_bool')
                    ->label('Так/Ні')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Активне')
                    ->boolean()
                    ->sortable()
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('sort')
                    ->label('Порядок')
                    ->sortable()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('characteristic_id')
                    ->label('Характеристика')
                    ->options(fn () => CharacteristicsProduct::query()
                        ->orderBy('name_uk')
                        ->pluck('name_uk', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload(),
            ])
            ->recordUrl(fn (CharacteristicValue $record): string =>
                route('filament.admin.resources.characteristic-values.edit', ['record' => $record])
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