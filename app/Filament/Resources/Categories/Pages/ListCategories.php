<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('structure')
                ->label('Структура каталогу')
                ->icon('heroicon-o-squares-2x2')
                ->color('info')
                ->url(static::getResource()::getUrl('structure')),

            CreateAction::make()
                ->label('Створити категорію')
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}