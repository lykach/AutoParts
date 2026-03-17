<?php

namespace App\Filament\Resources\PickupPointStoreStockSources;

use App\Filament\Resources\PickupPointStoreStockSources\Pages\CreatePickupPointStoreStockSource;
use App\Filament\Resources\PickupPointStoreStockSources\Pages\EditPickupPointStoreStockSource;
use App\Filament\Resources\PickupPointStoreStockSources\Pages\ListPickupPointStoreStockSources;
use App\Filament\Resources\PickupPointStoreStockSources\Schemas\PickupPointStoreStockSourceForm;
use App\Filament\Resources\PickupPointStoreStockSources\Tables\PickupPointStoreStockSourcesTable;
use App\Models\PickupPointStoreStockSource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PickupPointStoreStockSourceResource extends Resource
{
    protected static ?string $model = PickupPointStoreStockSource::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    protected static string|UnitEnum|null $navigationGroup = 'Логістика';

    protected static ?string $navigationLabel = 'Самовивіз: джерела';
    protected static ?string $modelLabel = 'джерело самовивозу';
    protected static ?string $pluralModelLabel = 'джерела самовивозу';
    protected static ?string $recordTitleAttribute = 'id';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'pickupPoint.store',
                'storeStockSource.store',
                'storeStockSource.stockSource',
                'storeStockSource.location',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return PickupPointStoreStockSourceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PickupPointStoreStockSourcesTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) PickupPointStoreStockSource::query()->count();
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
            'index'  => ListPickupPointStoreStockSources::route('/'),
            'create' => CreatePickupPointStoreStockSource::route('/create'),
            'edit'   => EditPickupPointStoreStockSource::route('/{record}/edit'),
        ];
    }
}