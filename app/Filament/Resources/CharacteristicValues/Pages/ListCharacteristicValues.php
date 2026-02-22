<?php

namespace App\Filament\Resources\CharacteristicValues\Pages;

use App\Filament\Resources\CharacteristicValues\CharacteristicValueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCharacteristicValues extends ListRecords
{
    protected static string $resource = CharacteristicValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Створити значення'),
        ];
    }
}
