<?php

namespace App\Filament\Resources\ArticleAnalogImportResource\Pages;

use App\Filament\Resources\ArticleAnalogImportResource\ArticleAnalogImportResource;
use Filament\Resources\Pages\ListRecords;

class ListArticleAnalogImports extends ListRecords
{
    protected static string $resource = ArticleAnalogImportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
