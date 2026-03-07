<?php

namespace App\Filament\Resources\MainPageGroups;

use App\Filament\Resources\MainPageGroups\Pages\CreateMainPageGroup;
use App\Filament\Resources\MainPageGroups\Pages\EditMainPageGroup;
use App\Filament\Resources\MainPageGroups\Pages\ListMainPageGroups;
use App\Filament\Resources\MainPageGroups\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\MainPageGroups\Schemas\MainPageGroupForm;
use App\Filament\Resources\MainPageGroups\Tables\MainPageGroupsTable;
use App\Models\MainPageGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class MainPageGroupResource extends Resource
{
    protected static ?string $model = MainPageGroup::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static string|UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?string $navigationLabel = 'Товари на головній';
    protected static ?string $modelLabel = 'блок товарів';
    protected static ?string $pluralModelLabel = 'блоки товарів';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return MainPageGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MainPageGroupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMainPageGroups::route('/'),
            'create' => CreateMainPageGroup::route('/create'),
            'edit' => EditMainPageGroup::route('/{record}/edit'),
        ];
    }
}