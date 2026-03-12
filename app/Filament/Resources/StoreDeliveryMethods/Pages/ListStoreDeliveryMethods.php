<?php

namespace App\Filament\Resources\StoreDeliveryMethods\Pages;

use App\Filament\Resources\StoreDeliveryMethods\StoreDeliveryMethodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStoreDeliveryMethods extends ListRecords
{
    protected static string $resource = StoreDeliveryMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Підключити доставку'),
        ];
    }
}