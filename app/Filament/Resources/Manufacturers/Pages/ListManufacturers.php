<?php

namespace App\Filament\Resources\Manufacturers\Pages;

use App\Filament\Resources\Manufacturers\ManufacturerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListManufacturers extends ListRecords
{
    protected static string $resource = ManufacturerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Створити виробника')
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
