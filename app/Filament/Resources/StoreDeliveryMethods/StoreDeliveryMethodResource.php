<?php

namespace App\Filament\Resources\StoreDeliveryMethods;

use App\Filament\Resources\StoreDeliveryMethods\Pages\CreateStoreDeliveryMethod;
use App\Filament\Resources\StoreDeliveryMethods\Pages\EditStoreDeliveryMethod;
use App\Filament\Resources\StoreDeliveryMethods\Pages\ListStoreDeliveryMethods;
use App\Filament\Resources\StoreDeliveryMethods\Schemas\StoreDeliveryMethodForm;
use App\Filament\Resources\StoreDeliveryMethods\Tables\StoreDeliveryMethodsTable;
use App\Models\StoreDeliveryMethod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StoreDeliveryMethodResource extends Resource
{
    protected static ?string $model = StoreDeliveryMethod::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';
    protected static string|UnitEnum|null $navigationGroup = 'Доставки';

    protected static ?string $navigationLabel = 'Доставки магазинів';
    protected static ?string $modelLabel = 'доставка магазину';
    protected static ?string $pluralModelLabel = 'доставки магазинів';

    public static function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['store', 'deliveryMethod']);
    }

    public static function form(Schema $schema): Schema
    {
        return StoreDeliveryMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoreDeliveryMethodsTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) StoreDeliveryMethod::query()->count();
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
            'index'  => ListStoreDeliveryMethods::route('/'),
            'create' => CreateStoreDeliveryMethod::route('/create'),
            'edit'   => EditStoreDeliveryMethod::route('/{record}/edit'),
        ];
    }
}