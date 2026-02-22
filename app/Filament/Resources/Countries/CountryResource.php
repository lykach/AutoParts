<?php

namespace App\Filament\Resources\Countries;

use App\Filament\Resources\Countries\Pages;
use App\Filament\Resources\Countries\Schemas\CountryForm;
use App\Filament\Resources\Countries\Tables\CountriesTable;
use App\Models\Country;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-globe-alt';
    }

    public static function getNavigationLabel(): string
    {
        return 'Країни';
    }

    public static function getModelLabel(): string
    {
        return 'Країна';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Країни';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Локалізація';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    // ✅ Badge з кількістю країн
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    // ✅ Колір badge
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() > 50 ? 'success' : 'primary';
    }

    // ✅ Tooltip для badge
    public static function getNavigationBadgeTooltip(): ?string
    {
        $count = static::getModel()::count();
        return "Всього країн: {$count}";
    }

    // ✅ Атрибут для заголовка запису
    public static function getRecordTitleAttribute(): ?string
    {
        return 'name_uk';
    }

    public static function form(Schema $schema): Schema
    {
        return CountryForm::make($schema);
    }

    public static function table(Table $table): Table
    {
        return CountriesTable::make($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCountries::route('/'),
            'create' => Pages\CreateCountry::route('/create'),
            'edit' => Pages\EditCountry::route('/{record}/edit'),
        ];
    }
}