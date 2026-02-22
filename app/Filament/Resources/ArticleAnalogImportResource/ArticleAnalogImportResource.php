<?php

namespace App\Filament\Resources\ArticleAnalogImportResource;

use App\Filament\Resources\ArticleAnalogImportResource\Pages\ListArticleAnalogImports;
use App\Filament\Resources\ArticleAnalogImportResource\Tables\ArticleAnalogImportsTable;
use App\Models\ArticleAnalogImport;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ArticleAnalogImportResource extends Resource
{
    protected static ?string $model = ArticleAnalogImport::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrow-up-tray';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Кроси / Антикроси';
    }

    public static function getNavigationLabel(): string
    {
        return 'Імпорти кросів';
    }

    public static function getModelLabel(): string
    {
        return 'Імпорт';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Імпорти';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return ArticleAnalogImportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticleAnalogImports::route('/'),
        ];
    }
}
