<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Filament\Resources\Roles\Tables\RolesTable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    public static function getNavigationLabel(): string
    {
        return 'Ролі';
    }

    public static function getModelLabel(): string
    {
        return 'Роль';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Ролі';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Адміністрування';
    }

    public static function getNavigationSort(): ?int
    {
        return 115;
    }

    /**
     * ✅ Навігацію показуємо по permissions (або super-admin)
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('super-admin')
            || $user?->can('roles.view')
            || $user?->can('roles.create')
            || $user?->can('roles.update')
            || $user?->can('roles.delete')
            || false;
    }

    /**
     * ✅ Badge біля "Ролі"
     */
    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    /**
     * ✅ Колір бейджа
     */
    public static function getNavigationBadgeColor(): ?string
    {
        try {
            $hasSuperAdmin = static::getModel()::where('name', 'super-admin')->exists();
            return $hasSuperAdmin ? 'success' : 'warning';
        } catch (\Throwable $e) {
            return 'primary';
        }
    }

    /**
     * ✅ Tooltip
     */
    public static function getNavigationBadgeTooltip(): ?string
    {
        try {
            $total = static::getModel()::count();
            $admins = static::getModel()
                ->whereIn('name', ['admin', 'super-admin'])
                ->count();

            return "Ролей: {$total} | Адмінських: {$admins}";
        } catch (\Throwable $e) {
            return 'Кількість ролей: ' . static::getModel()::count();
        }
    }

    public static function form(Schema $schema): Schema
    {
        return RoleForm::make($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::make($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['users', 'permissions']);
    }
}
