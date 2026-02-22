<?php

namespace App\Filament\Resources\Manufacturers\Pages;

use App\Filament\Resources\Manufacturers\ManufacturerResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateManufacturer extends CreateRecord
{
    protected static string $resource = ManufacturerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Виробника створено')
            ->body('Виробник успішно доданий.');
    }
}
