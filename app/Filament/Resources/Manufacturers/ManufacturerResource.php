<?php

namespace App\Filament\Resources\Manufacturers;

use App\Filament\Resources\Manufacturers\Pages\CreateManufacturer;
use App\Filament\Resources\Manufacturers\Pages\EditManufacturer;
use App\Filament\Resources\Manufacturers\Pages\ListManufacturers;
use App\Filament\Resources\Manufacturers\RelationManagers\ManufacturerSynonymsRelationManager;
use App\Filament\Resources\Manufacturers\Schemas\ManufacturerForm;
use App\Filament\Resources\Manufacturers\Tables\ManufacturersTable;
use App\Models\Manufacturer;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ManufacturerResource extends Resource
{
    protected static ?string $model = Manufacturer::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-building-storefront';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Каталог';
    }

    public static function getNavigationLabel(): string
    {
        return 'Виробники';
    }

    public static function getModelLabel(): string
    {
        return 'Виробник';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Виробники';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'name';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return ManufacturerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ManufacturersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ManufacturerSynonymsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListManufacturers::route('/'),
            'create' => CreateManufacturer::route('/create'),
            'edit' => EditManufacturer::route('/{record}/edit'),
        ];
    }
}
