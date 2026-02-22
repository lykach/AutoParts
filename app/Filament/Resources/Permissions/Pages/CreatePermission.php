<?php

namespace App\Filament\Resources\Permissions\Pages;

use App\Filament\Resources\Permissions\PermissionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePermission extends CreateRecord
{
    protected static string $resource = PermissionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // guard завжди web
        $data['guard_name'] = 'web';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Permission створено')
            ->body("Право '{$this->record->name}' додано.");
    }
}
