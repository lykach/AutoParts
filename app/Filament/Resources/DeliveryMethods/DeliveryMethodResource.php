<?php

namespace App\Filament\Resources\DeliveryMethods;

use App\Filament\Resources\DeliveryMethods\Pages\CreateDeliveryMethod;
use App\Filament\Resources\DeliveryMethods\Pages\EditDeliveryMethod;
use App\Filament\Resources\DeliveryMethods\Pages\ListDeliveryMethods;
use App\Filament\Resources\DeliveryMethods\Schemas\DeliveryMethodForm;
use App\Filament\Resources\DeliveryMethods\Tables\DeliveryMethodsTable;
use App\Models\DeliveryMethod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DeliveryMethodResource extends Resource
{
    protected static ?string $model = DeliveryMethod::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';
    protected static string|UnitEnum|null $navigationGroup = 'Логістика';

    protected static ?string $navigationLabel = 'Довідник доставок';
    protected static ?string $modelLabel = 'спосіб доставки';
    protected static ?string $pluralModelLabel = 'способи доставки';

    public static function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('storeLinks');
    }

    public static function form(Schema $schema): Schema
    {
        return DeliveryMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliveryMethodsTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) DeliveryMethod::query()->count();
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
            'index'  => ListDeliveryMethods::route('/'),
            'create' => CreateDeliveryMethod::route('/create'),
            'edit'   => EditDeliveryMethod::route('/{record}/edit'),
        ];
    }
}