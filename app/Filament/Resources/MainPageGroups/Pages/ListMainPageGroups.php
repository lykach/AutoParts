<?php

namespace App\Filament\Resources\MainPageGroups\Pages;

use App\Filament\Resources\MainPageGroups\MainPageGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMainPageGroups extends ListRecords
{
    protected static string $resource = MainPageGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}