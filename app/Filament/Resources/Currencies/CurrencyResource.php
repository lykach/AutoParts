<?php
namespace App\Filament\Resources\Currencies;

use App\Filament\Resources\Currencies\Pages\CreateCurrency;
use App\Filament\Resources\Currencies\Pages\EditCurrency;
use App\Filament\Resources\Currencies\Pages\ListCurrencies;
use App\Filament\Resources\Currencies\Schemas\CurrencyForm;
use App\Filament\Resources\Currencies\Tables\CurrenciesTable;
use App\Models\Currency;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    // ✅ МЕТОДИ замість властивостей (Filament 5.x)
    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Локалізація';
    }
    
    public static function getNavigationLabel(): string
    {
        return 'Валюти';
    }
    
    public static function getModelLabel(): string
    {
        return 'Валюта';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'Валюти';
    }
    
    public static function getRecordTitleAttribute(): ?string
    {
        return 'code';
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return CurrencyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CurrenciesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCurrencies::route('/'),
            'create' => CreateCurrency::route('/create'),
            'edit' => EditCurrency::route('/{record}/edit'),
        ];
    }
}