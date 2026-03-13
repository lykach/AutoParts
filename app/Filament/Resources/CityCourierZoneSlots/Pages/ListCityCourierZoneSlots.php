<?php

namespace App\Filament\Resources\CityCourierZoneSlots\Pages;

use App\Filament\Resources\CityCourierZoneSlots\CityCourierZoneSlotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCityCourierZoneSlots extends ListRecords
{
    protected static string $resource = CityCourierZoneSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Створити слот'),
        ];
    }
}