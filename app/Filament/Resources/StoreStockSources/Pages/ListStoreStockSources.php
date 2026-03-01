<?php

namespace App\Filament\Resources\StoreStockSources\Pages;

use App\Filament\Resources\StoreStockSources\StoreStockSourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStoreStockSources extends ListRecords
{
    protected static string $resource = StoreStockSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Підключити склад'),
        ];
    }
}