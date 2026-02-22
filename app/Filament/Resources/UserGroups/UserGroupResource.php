<?php

namespace App\Filament\Resources\UserGroups;

use App\Filament\Resources\UserGroups\Pages;
use App\Filament\Resources\UserGroups\Schemas\UserGroupForm;
use App\Filament\Resources\UserGroups\Tables\UserGroupsTable;
use App\Models\UserGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class UserGroupResource extends Resource
{
    protected static ?string $model = UserGroup::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-m-user-group';

    public static function getNavigationLabel(): string
    {
        return 'Групи користувачів';
    }

    public static function getModelLabel(): string
    {
        return 'Група користувачів';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Групи користувачів';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Адміністрування';
    }

    public static function getNavigationSort(): ?int
    {
        return 110;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'name';
    }

    public static function form(Schema $schema): Schema
    {
        return UserGroupForm::make($schema);
    }

    public static function table(Table $table): Table
    {
        return UserGroupsTable::make($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserGroups::route('/'),
            'create' => Pages\CreateUserGroup::route('/create'),
            'edit' => Pages\EditUserGroup::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
