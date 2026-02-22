<?php
namespace App\Filament\Resources\CategoryMirrors\Pages;

use App\Filament\Resources\CategoryMirrors\CategoryMirrorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategoryMirrors extends ListRecords
{
    protected static string $resource = CategoryMirrorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Створити дублікат'),
        ];
    }
}