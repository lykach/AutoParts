<?php

namespace App\Filament\Resources\Countries\Pages;

use App\Filament\Resources\Countries\CountryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCountries extends ListRecords
{
    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus-circle')
                ->label('Створити країну'),
        ];
    }

    // ✅ Статистика в хедері (опціонально)
    protected function getHeaderWidgets(): array
    {
        return [];
    }
}