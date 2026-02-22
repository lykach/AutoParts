<?php

namespace App\Filament\Resources\ArticleAnalogExportResource\Pages;

use App\Filament\Resources\ArticleAnalogExportResource\ArticleAnalogExportResource;
use Filament\Resources\Pages\ListRecords;

class ListArticleAnalogExports extends ListRecords
{
    protected static string $resource = ArticleAnalogExportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
