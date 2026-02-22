<?php

namespace App\Filament\Resources\ArticleAnalogResource;

use App\Filament\Resources\ArticleAnalogResource\Pages\CreateArticleAnalog;
use App\Filament\Resources\ArticleAnalogResource\Pages\EditArticleAnalog;
use App\Filament\Resources\ArticleAnalogResource\Pages\ListArticleAnalogs;
use App\Filament\Resources\ArticleAnalogResource\Schemas\ArticleAnalogForm;
use App\Filament\Resources\ArticleAnalogResource\Tables\ArticleAnalogsTable;
use App\Models\ArticleAnalog;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ArticleAnalogResource extends Resource
{
    protected static ?string $model = ArticleAnalog::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrows-right-left';
    }

    public static function getNavigationSort(): ?int
    {
        return 25;
    }

    public static function getNavigationLabel(): string
    {
        return 'Кроси / Антикроси';
    }

    public static function getModelLabel(): string
    {
        return 'Крос / Антикрос';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Кроси / Антикроси';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Кроси / Антикроси';
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin') || $user?->can('article-analogs.view'));
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin') || $user?->can('article-analogs.create'));
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin') || $user?->can('article-analogs.update'));
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        return (bool) ($user?->hasRole('super-admin') || $user?->can('article-analogs.delete'));
    }

    public static function form(Schema $schema): Schema
    {
        // ✅ як у документації v5: виносимо в окремий клас і повертаємо Schema
        return ArticleAnalogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticleAnalogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListArticleAnalogs::route('/'),
            'create' => CreateArticleAnalog::route('/create'),
            'edit'   => EditArticleAnalog::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
