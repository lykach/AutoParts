<?php

namespace App\Filament\Resources\CategoryMirrors;

use App\Filament\Resources\CategoryMirrors\Pages\CreateCategoryMirror;
use App\Filament\Resources\CategoryMirrors\Pages\EditCategoryMirror;
use App\Filament\Resources\CategoryMirrors\Pages\ListCategoryMirrors;
use App\Filament\Resources\CategoryMirrors\Schemas\CategoryMirrorForm;
use App\Filament\Resources\CategoryMirrors\Tables\CategoryMirrorsTable;
use App\Models\CategoryMirror;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CategoryMirrorResource extends Resource
{
    protected static ?string $model = CategoryMirror::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-link';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Каталог';
    }

    public static function getNavigationLabel(): string
    {
        return 'Дублікати категорій';
    }

    public static function getModelLabel(): string
    {
        return 'Дублікат';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Дублікати категорій';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    /**
     * ✅ RBAC: доступ до розділу
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin') || $user?->can('category-mirrors.view'));
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin') || $user?->can('category-mirrors.create'));
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin') || $user?->can('category-mirrors.update'));
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin') || $user?->can('category-mirrors.delete'));
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return CategoryMirrorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoryMirrorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCategoryMirrors::route('/'),
            'create' => CreateCategoryMirror::route('/create'),
            'edit'   => EditCategoryMirror::route('/{record}/edit'),
        ];
    }
}
