<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Permission;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['confirm_permissions_change']);
        $data['guard_name'] = 'web';

        return $data;
    }

    protected function afterCreate(): void
    {
        $ids = array_values(array_filter(array_map(
            fn ($v) => is_numeric($v) ? (int) $v : null,
            (array) ($this->data['permissions'] ?? [])
        )));

        if ($this->record && array_key_exists('permissions', $this->data)) {
            $permissions = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('id', $ids)
                ->get();

            $this->record->syncPermissions($permissions);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Роль створено')
            ->body("Роль '{$this->record->name}' успішно створена.");
    }
}
