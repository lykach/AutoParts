<?php

namespace App\Filament\Resources\Countries\Tables;

use App\Filament\Resources\Countries\CountryResource;
use App\Models\Country;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CountriesTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('flag_image')
                    ->label('Прапор')
                    ->disk('public')
                    ->size(40)
                    ->circular()
                    ->defaultImageUrl(null)
                    ->toggleable(),

                TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Код скопійовано')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('name_uk')
                    ->label('Назва')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Country $record) => $record->name_en ?: null)
                    ->limit(50),

                TextColumn::make('iso_code_2')
                    ->label('ISO-2')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('iso_code_3')
                    ->label('ISO-3')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('currency_code')
                    ->label('Валюта')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-banknotes')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable()
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Всі країни')
                    ->trueLabel('Тільки активні')
                    ->falseLabel('Тільки неактивні'),

                TernaryFilter::make('is_group')
                    ->label('Тип')
                    ->placeholder('Всі')
                    ->trueLabel('Групи країн')
                    ->falseLabel('Окремі країни'),

                SelectFilter::make('currency_code')
                    ->label('Валюта')
                    ->options(fn () => Country::query()
                        ->whereNotNull('currency_code')
                        ->distinct()
                        ->orderBy('currency_code')
                        ->pluck('currency_code', 'currency_code')
                        ->toArray()
                    )
                    ->searchable()
                    ->multiple(),
            ])
            ->recordUrl(fn (Country $record) => CountryResource::getUrl('edit', ['record' => $record]))
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-globe-alt')
            ->emptyStateHeading('Немає країн')
            ->emptyStateDescription('Створіть першу країну, натиснувши кнопку нижче.')
            ->striped();
    }
}
