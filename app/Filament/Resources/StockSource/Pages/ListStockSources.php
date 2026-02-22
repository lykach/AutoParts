<?php

namespace App\Filament\Resources\StockSource\Pages;

use App\Filament\Resources\StockSource\StockSourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockSources extends ListRecords
{
    protected static string $resource = StockSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Створити джерело'),
        ];
    }
}
