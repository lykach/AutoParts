<?php

namespace App\Filament\Resources\CharacteristicsProduct\Pages;

use App\Filament\Resources\CharacteristicsProduct\CharacteristicsProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCharacteristicsProduct extends ListRecords
{
    protected static string $resource = CharacteristicsProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
			->label('Створити характеристику')
			->icon('heroicon-m-plus-circle'),
        ];
    }
}