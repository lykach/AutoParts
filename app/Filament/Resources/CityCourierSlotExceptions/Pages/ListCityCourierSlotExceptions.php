<?php

namespace App\Filament\Resources\CityCourierSlotExceptions\Pages;

use App\Filament\Resources\CityCourierSlotExceptions\CityCourierSlotExceptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCityCourierSlotExceptions extends ListRecords
{
    protected static string $resource = CityCourierSlotExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Створити виняток'),
        ];
    }
}