<?php

namespace App\Filament\Resources\Store;

use App\Filament\Resources\Store\Pages\CreateStore;
use App\Filament\Resources\Store\Pages\EditStore;
use App\Filament\Resources\Store\Pages\ListStores;
use App\Filament\Resources\Store\Schemas\StoreForm;
use App\Filament\Resources\Store\Tables\StoreTable;
use App\Models\Store;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    // ✅ Filament v5 type requirement
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Магазини / Філії';
    protected static ?string $modelLabel = 'магазин';
    protected static ?string $pluralModelLabel = 'магазини';

    protected static ?string $recordTitleAttribute = 'display_name';

    // ✅ Робимо контент на всю ширину (прибирає “вузьке” відображення)
    public static function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public static function form(Schema $schema): Schema
    {
        return StoreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoreTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListStores::route('/'),
            'create' => CreateStore::route('/create'),
            'edit'   => EditStore::route('/{record}/edit'),
        ];
    }
}
