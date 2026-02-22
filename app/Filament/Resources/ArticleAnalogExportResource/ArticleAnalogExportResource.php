<?php

namespace App\Filament\Resources\ArticleAnalogExportResource;

use App\Filament\Resources\ArticleAnalogExportResource\Pages\ListArticleAnalogExports;
use App\Filament\Resources\ArticleAnalogExportResource\Tables\ArticleAnalogExportsTable;
use App\Models\ArticleAnalogExport;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ArticleAnalogExportResource extends Resource
{
    protected static ?string $model = ArticleAnalogExport::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrow-down-tray';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Кроси / Антикроси';
    }

    public static function getNavigationLabel(): string
    {
        return 'Експорти кросів';
    }

    public static function getModelLabel(): string
    {
        return 'Експорт';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Експорти';
    }

    public static function form(Schema $schema): Schema
    {
        // Не потрібно створення/редагування вручну
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return ArticleAnalogExportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticleAnalogExports::route('/'),
        ];
    }
}
