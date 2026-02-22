<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Role;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Користувача створено')
            ->body("Користувач '{$this->record->name}' успішно доданий до системи.");
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['email_verified_at'] = now();

        // ✅ Backend-захист: admin/manager не може призначити super-admin навіть через підміну запиту
        if (isset($data['roles']) && is_array($data['roles'])) {
            $me = auth()->user();

            $allowedRoleIds = Role::query()
                ->when(! $me?->hasRole('super-admin'), fn ($q) => $q->where('name', '!=', 'super-admin'))
                ->pluck('id')
                ->all();

            $data['roles'] = array_values(array_intersect($data['roles'], $allowedRoleIds));
        }

        return $data;
    }
}
