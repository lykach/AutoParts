<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;

use App\Filament\Resources\Products\RelationManagers\CharacteristicsRelationManager;
use App\Filament\Resources\Products\RelationManagers\DetailsRelationManager;
use App\Filament\Resources\Products\RelationManagers\OemNumbersRelationManager;
use App\Filament\Resources\Products\RelationManagers\ProductBarcodesRelationManager;
use App\Filament\Resources\Products\RelationManagers\ProductComponentsRelationManager;
use App\Filament\Resources\Products\RelationManagers\ProductFilesRelationManager;
use App\Filament\Resources\Products\RelationManagers\ProductImagesRelationManager;
use App\Filament\Resources\Products\RelationManagers\RelatedProductsRelationManager;
use App\Filament\Resources\Products\RelationManagers\StockItemsRelationManager;

use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|UnitEnum|null $navigationGroup = 'Каталог';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShoppingBag;

    protected static ?string $modelLabel = 'товар';
    protected static ?string $pluralModelLabel = 'товари';

    public static function getNavigationLabel(): string
    {
        return 'Товари';
    }

    public static function getLabel(): ?string
    {
        return 'Товар';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Товари';
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) Product::query()->count();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    protected static ?string $recordTitleAttribute = 'article_raw';

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    /**
     * ✅ ВАЖЛИВО: тут має бути ТІЛЬКИ ОДИН getRelations()
     */
    public static function getRelations(): array
    {
        return [
            StockItemsRelationManager::class,
            OemNumbersRelationManager::class,
            ProductComponentsRelationManager::class,
            ProductBarcodesRelationManager::class,
            ProductFilesRelationManager::class,
            RelatedProductsRelationManager::class,
            ProductImagesRelationManager::class,

            // ✅ ДОДАЛИ НОВЕ:
            DetailsRelationManager::class,
            CharacteristicsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit'   => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}