<?php

namespace App\Filament\Resources\DeliveryPickupPoints\Pages;

use App\Filament\Resources\DeliveryPickupPoints\DeliveryPickupPointResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryPickupPoints extends ListRecords
{
    protected static string $resource = DeliveryPickupPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Створити точку самовивозу'),
        ];
    }
}