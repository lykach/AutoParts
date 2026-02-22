<?php

namespace App\Filament\Resources\Manufacturers\Tables;

use App\Models\Manufacturer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ManufacturersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label('Лого')
                    ->disk('public')
                    ->square()
                    ->size(40)
                    ->defaultImageUrl(null),

                TextColumn::make('name')
                    ->label('Виробник')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Manufacturer $record) => 'Slug: ' . ($record->slug ?? '—')),

                TextColumn::make('short_name')
                    ->label('Коротка назва')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->badge()
                    ->toggleable(),

                IconColumn::make('is_oem')
                    ->label('OEM')
                    ->boolean()
                    ->sortable()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                TextColumn::make('internal_catalog_url_uk')
                    ->label('URL (UK)')
                    ->badge()
                    ->copyable()
                    ->copyMessage('URL скопійовано')
                    ->url(fn (Manufacturer $record) => $record->internal_catalog_url_uk ?: null, shouldOpenInNewTab: true)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('country.name_uk')
                    ->label('Країна')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('synonyms_count')
                    ->label('Синонімів')
                    ->counts('synonyms')
                    ->badge()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Активний')
                    ->boolean()
                    ->sortable()
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки активні')
                    ->falseLabel('Неактивні'),

                TernaryFilter::make('is_oem')
                    ->label('OEM')
                    ->placeholder('Всі')
                    ->trueLabel('Тільки OEM')
                    ->falseLabel('Не OEM'),
            ])
            ->recordUrl(
                fn (Manufacturer $record): string =>
                    route('filament.admin.resources.manufacturers.edit', ['record' => $record])
            )
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50);
    }
}