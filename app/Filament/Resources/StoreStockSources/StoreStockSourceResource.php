<?php

namespace App\Filament\Resources\StoreStockSources;

use App\Filament\Resources\StoreStockSources\Pages\CreateStoreStockSource;
use App\Filament\Resources\StoreStockSources\Pages\EditStoreStockSource;
use App\Filament\Resources\StoreStockSources\Pages\ListStoreStockSources;
use App\Filament\Resources\StoreStockSources\Schemas\StoreStockSourceForm;
use App\Filament\Resources\StoreStockSources\Tables\StoreStockSourcesTable;
use App\Models\StoreStockSource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StoreStockSourceResource extends Resource
{
    protected static ?string $model = StoreStockSource::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    protected static string|UnitEnum|null $navigationGroup = 'Склади';

    protected static ?string $navigationLabel = 'Склади магазинів';
    protected static ?string $modelLabel = 'склад магазину';
    protected static ?string $pluralModelLabel = 'склади магазинів';

    public static function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    /**
     * ✅ Критично для стабільності:
     * Завжди підвантажуємо відносини в базовому запиті ресурсу,
     * щоб таблиця не “вигадувала” join-и по relation.field.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['store', 'stockSource', 'location']);
    }

    public static function form(Schema $schema): Schema
    {
        return StoreStockSourceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoreStockSourcesTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) StoreStockSource::query()->count();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListStoreStockSources::route('/'),
            'create' => CreateStoreStockSource::route('/create'),
            'edit'   => EditStoreStockSource::route('/{record}/edit'),
        ];
    }
}