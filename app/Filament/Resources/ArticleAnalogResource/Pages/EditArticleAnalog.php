<?php

namespace App\Filament\Resources\ArticleAnalogResource\Pages;

use App\Filament\Resources\ArticleAnalogResource\ArticleAnalogResource;
use Filament\Resources\Pages\EditRecord;

class EditArticleAnalog extends EditRecord
{
    protected static string $resource = ArticleAnalogResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
