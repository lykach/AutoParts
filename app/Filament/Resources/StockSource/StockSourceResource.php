<?php

namespace App\Filament\Resources\StockSource;

use App\Filament\Resources\StockSource\Pages\CreateStockSource;
use App\Filament\Resources\StockSource\Pages\EditStockSource;
use App\Filament\Resources\StockSource\Pages\ListStockSources;
use App\Filament\Resources\StockSource\RelationManagers\LocationsRelationManager;
use App\Filament\Resources\StockSource\Schemas\StockSourceForm;
use App\Filament\Resources\StockSource\Tables\StockSourceTable;
use App\Models\StockSource;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class StockSourceResource extends Resource
{
    protected static ?string $model = StockSource::class;

    protected static ?string $modelLabel = 'джерело';
    protected static ?string $pluralModelLabel = 'джерела';
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return 'Склади / Постачальники';
    }

    public static function getNavigationGroup(): string
    {
        return 'Магазин';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-truck';
    }

    public static function form(Schema $schema): Schema
    {
        return StockSourceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockSourceTable::configure($table);
    }

    /**
     * ✅ Filament v5: relations повертаємо як масив КЛАСІВ.
     * Це дає вкладку “Склади постачальника” в Edit сторінці.
     */
    public static function getRelations(): array
    {
        return [
            LocationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListStockSources::route('/'),
            'create' => CreateStockSource::route('/create'),
            'edit'   => EditStockSource::route('/{record}/edit'),
        ];
    }
}