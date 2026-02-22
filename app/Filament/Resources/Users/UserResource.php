<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    public static function getNavigationLabel(): string
    {
        return 'Користувачі';
    }

    public static function getModelLabel(): string
    {
        return 'Користувач';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Користувачі';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Адміністрування';
    }

    public static function getNavigationSort(): ?int
    {
        return 100;
    }

    /**
     * ✅ Badge з загальною кількістю користувачів
     */
    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    /**
     * ✅ Колір бейджа залежить від наявності адміністраторів
     */
    public static function getNavigationBadgeColor(): ?string
    {
        try {
            $hasAdmins = static::getModel()::role(['admin', 'super-admin'])->exists();
            return $hasAdmins ? 'success' : 'warning';
        } catch (\Exception $e) {
            return 'primary';
        }
    }

    /**
     * ✅ Розширений тултіп для навігації
     */
    public static function getNavigationBadgeTooltip(): ?string
    {
        try {
            $total = static::getModel()::count();
            $admins = static::getModel()::role(['admin', 'super-admin'])->count();
            return "Всього: {$total} | Адмінів: {$admins}";
        } catch (\Exception $e) {
            return "Всього користувачів: " . static::getModel()::count();
        }
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'name';
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::make($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::make($table);
    }

    /**
     * ✅ Глобальний пошук
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * ✅ Eager Loading для оптимізації
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['roles', 'group']);
    }
}