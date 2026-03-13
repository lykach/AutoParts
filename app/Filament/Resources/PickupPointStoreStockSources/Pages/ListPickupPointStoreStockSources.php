<?php

namespace App\Filament\Resources\PickupPointStoreStockSources\Pages;

use App\Filament\Resources\PickupPointStoreStockSources\PickupPointStoreStockSourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPickupPointStoreStockSources extends ListRecords
{
    protected static string $resource = PickupPointStoreStockSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Підключити джерело'),
        ];
    }
}