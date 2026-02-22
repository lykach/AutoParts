<?php

namespace App\Filament\Resources\Countries\Pages;

use App\Filament\Resources\Countries\CountryResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCountry extends EditRecord
{
    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Видалити країну?')
                ->modalDescription('Ви впевнені, що хочете видалити цю країну? Цю дію не можна скасувати.')
                ->modalSubmitActionLabel('Так, видалити')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Країну видалено')
                        ->body('Країна успішно видалена з системи.')
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // ✅ Кастомна нотифікація після оновлення
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Зміни збережено')
            ->body("Країна '{$this->record->name_uk}' успішно оновлена.");
    }
}