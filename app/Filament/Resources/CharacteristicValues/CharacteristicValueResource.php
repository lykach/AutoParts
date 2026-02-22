<?php

namespace App\Filament\Resources\CharacteristicValues;

use App\Filament\Resources\CharacteristicValues\Pages\CreateCharacteristicValue;
use App\Filament\Resources\CharacteristicValues\Pages\EditCharacteristicValue;
use App\Filament\Resources\CharacteristicValues\Pages\ListCharacteristicValues;
use App\Filament\Resources\CharacteristicValues\Schemas\CharacteristicValueForm;
use App\Filament\Resources\CharacteristicValues\Tables\CharacteristicValuesTable;
use App\Models\CharacteristicValue;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CharacteristicValueResource extends Resource
{
    protected static ?string $model = CharacteristicValue::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-tag';
    }

    public static function getNavigationSort(): ?int
    {
        return 31;
    }

    public static function getNavigationLabel(): string
    {
        return 'Значення характеристик';
    }

    public static function getModelLabel(): string
    {
        return 'Значення';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Значення';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Каталог';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema(CharacteristicValueForm::schema());
    }

    public static function table(Table $table): Table
    {
        return CharacteristicValuesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCharacteristicValues::route('/'),
            'create' => CreateCharacteristicValue::route('/create'),
            'edit'   => EditCharacteristicValue::route('/{record}/edit'),
        ];
    }
	
	public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
