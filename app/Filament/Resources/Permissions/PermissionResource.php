<?php

namespace App\Filament\Resources\Permissions;

use App\Filament\Resources\Permissions\Pages;
use App\Filament\Resources\Permissions\Schemas\PermissionForm;
use App\Filament\Resources\Permissions\Tables\PermissionsTable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    public static function getNavigationLabel(): string
    {
        return 'Права доступу';
    }

    public static function getModelLabel(): string
    {
        return 'Право доступу';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Права доступу';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Адміністрування';
    }

    public static function getNavigationSort(): ?int
    {
        return 116;
    }

    /**
     * ✅ Навігацію показуємо по permissions (або super-admin)
     * (Policy все одно є основним захистом)
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('super-admin')
            || $user?->can('permissions.view')
            || $user?->can('permissions.create')
            || $user?->can('permissions.update')
            || $user?->can('permissions.delete')
            || false;
    }

    /**
     * ✅ Бейдж як в інших модулях
     */
    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return PermissionForm::make($schema);
    }

    public static function table(Table $table): Table
    {
        return PermissionsTable::make($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['roles']);
    }
}
