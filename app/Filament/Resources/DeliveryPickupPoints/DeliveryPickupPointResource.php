<?php

namespace App\Filament\Resources\DeliveryPickupPoints;

use App\Filament\Resources\DeliveryPickupPoints\Pages\CreateDeliveryPickupPoint;
use App\Filament\Resources\DeliveryPickupPoints\Pages\EditDeliveryPickupPoint;
use App\Filament\Resources\DeliveryPickupPoints\Pages\ListDeliveryPickupPoints;
use App\Filament\Resources\DeliveryPickupPoints\RelationManagers\PickupSourcesRelationManager;
use App\Filament\Resources\DeliveryPickupPoints\Schemas\DeliveryPickupPointForm;
use App\Filament\Resources\DeliveryPickupPoints\Tables\DeliveryPickupPointsTable;
use App\Models\DeliveryPickupPoint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DeliveryPickupPointResource extends Resource
{
    protected static ?string $model = DeliveryPickupPoint::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';
    protected static string|UnitEnum|null $navigationGroup = 'Доставки';

    protected static ?string $navigationLabel = 'Точки самовивозу';
    protected static ?string $modelLabel = 'точка самовивозу';
    protected static ?string $pluralModelLabel = 'точки самовивозу';
    protected static ?string $recordTitleAttribute = 'name';

    public static function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['store'])
            ->withCount([
                'stockSourceLinks',
                'stockSourceLinks as active_stock_source_links_count' => fn (Builder $query) => $query->where('is_active', true),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return DeliveryPickupPointForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliveryPickupPointsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PickupSourcesRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) DeliveryPickupPoint::query()->count();
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
            'index'  => ListDeliveryPickupPoints::route('/'),
            'create' => CreateDeliveryPickupPoint::route('/create'),
            'edit'   => EditDeliveryPickupPoint::route('/{record}/edit'),
        ];
    }
}