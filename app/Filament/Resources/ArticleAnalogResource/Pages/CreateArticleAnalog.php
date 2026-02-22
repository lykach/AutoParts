<?php

namespace App\Filament\Resources\ArticleAnalogResource\Pages;

use App\Filament\Resources\ArticleAnalogResource\ArticleAnalogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArticleAnalog extends CreateRecord
{
    protected static string $resource = ArticleAnalogResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
