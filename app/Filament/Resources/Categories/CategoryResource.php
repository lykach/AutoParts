<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Categories\Tables\CategoriesTable;
use App\Filament\Resources\Categories\RelationManagers\CategoryCharacteristicsRelationManager;
use App\Models\Category;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-folder';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationLabel(): string
    {
        return 'Категорії';
    }

    public static function getModelLabel(): string
    {
        return 'Категорія';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Категорії';
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
        return (bool) ($user?->hasRole('super-admin') || $user?->can('categories.view'));
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin') || $user?->can('categories.create'));
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin') || $user?->can('categories.update'));
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin') || $user?->can('categories.delete'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema(CategoryForm::schema());
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
	
	public static function getRelations(): array
    {
        return [
            CategoryCharacteristicsRelationManager::class,
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
