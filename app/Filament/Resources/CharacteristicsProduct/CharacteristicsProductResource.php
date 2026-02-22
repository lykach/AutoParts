<?php

namespace App\Filament\Resources\CharacteristicsProduct;

use App\Filament\Resources\CharacteristicsProduct\Pages\CreateCharacteristicsProduct;
use App\Filament\Resources\CharacteristicsProduct\Pages\EditCharacteristicsProduct;
use App\Filament\Resources\CharacteristicsProduct\Pages\ListCharacteristicsProduct;
use App\Filament\Resources\CharacteristicsProduct\RelationManagers\ValuesRelationManager;
use App\Filament\Resources\CharacteristicsProduct\Schemas\CharacteristicsProductForm;
use App\Filament\Resources\CharacteristicsProduct\Tables\CharacteristicsProductTable;
use App\Models\CharacteristicsProduct;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CharacteristicsProductResource extends Resource
{
    protected static ?string $model = CharacteristicsProduct::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-adjustments-horizontal';
    }

    public static function getNavigationSort(): ?int
    {
        return 30;
    }

    public static function getNavigationLabel(): string
    {
        return 'Характеристики товару';
    }

    public static function getModelLabel(): string
    {
        return 'Характеристика';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Характеристики';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Каталог';
    }

    /**
     * ✅ RBAC: доступ до розділу
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin') || $user?->can('characteristics_products.view'));
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin') || $user?->can('characteristics_products.create'));
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin') || $user?->can('characteristics_products.update'));
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin') || $user?->can('characteristics_products.delete'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema(CharacteristicsProductForm::schema());
    }

    public static function table(Table $table): Table
    {
        return CharacteristicsProductTable::configure($table);
    }

    /**
     * ✅ Relation Managers (вкладки/зв'язки)
     */
    public static function getRelations(): array
    {
        return [
            ValuesRelationManager::class, // ✅ Значення характеристики
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCharacteristicsProduct::route('/'),
            'create' => CreateCharacteristicsProduct::route('/create'),
            'edit'   => EditCharacteristicsProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
