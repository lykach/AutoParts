<?php

namespace App\Filament\Resources\Manufacturers\Pages;

use App\Filament\Resources\Manufacturers\ManufacturerResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditManufacturer extends EditRecord
{
    protected static string $resource = ManufacturerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Виробника видалено')
                        ->body('Виробник і його синоніми видалені.')
                )
                ->successRedirectUrl(static::getResource()::getUrl('index')),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Виробника оновлено')
            ->body('Зміни успішно збережено.');
    }
}
