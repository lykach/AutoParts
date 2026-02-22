<?php

namespace App\Filament\Resources\UserGroups\Pages;

use App\Filament\Resources\UserGroups\UserGroupResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUserGroup extends CreateRecord
{
    protected static string $resource = UserGroupResource::class;

    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->title('Групу створено')
            ->body("Група '{$this->record->name}' успішно додана.")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
