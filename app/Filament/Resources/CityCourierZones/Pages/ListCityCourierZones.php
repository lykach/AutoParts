<?php

namespace App\Filament\Resources\CityCourierZones\Pages;

use App\Filament\Resources\CityCourierZones\CityCourierZoneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCityCourierZones extends ListRecords
{
    protected static string $resource = CityCourierZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Створити зону'),
        ];
    }
}