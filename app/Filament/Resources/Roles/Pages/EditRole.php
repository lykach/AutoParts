<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Permission;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // guard завжди web
        $data['guard_name'] = 'web';

        // технічний чекбокс не зберігаємо
        unset($data['confirm_permissions_change']);

        return $data;
    }

    protected function afterSave(): void
    {
        // permissions приходять як ["33","34",...]
        $ids = array_values(array_filter(array_map(
            fn ($v) => is_numeric($v) ? (int) $v : null,
            (array) ($this->data['permissions'] ?? [])
        )));

        // Sync тільки якщо поле було у формі (щоб випадково не стерти)
        if (array_key_exists('permissions', $this->data)) {
            $permissions = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('id', $ids)
                ->get();

            $this->record->syncPermissions($permissions);
        }

        Notification::make()
            ->success()
            ->title('Зміни збережено')
            ->body("Роль '{$this->record->name}' успішно оновлена.")
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Видалити')
                ->requiresConfirmation()
                ->modalHeading('Видалити роль?')
                ->modalDescription('Ця дія видалить роль назавжди. Продовжити?')
                ->modalSubmitActionLabel('Так, видалити')
                ->hidden(fn () => ($this->record?->name ?? '') === 'super-admin')
                ->before(function (Actions\DeleteAction $action) {
                    if (! $this->record) {
                        $action->halt();
                        return;
                    }

                    if ($this->record->name === 'super-admin') {
                        Notification::make()
                            ->warning()
                            ->title('Неможливо видалити')
                            ->body('Роль super-admin видаляти заборонено.')
                            ->send();

                        $action->halt();
                        return;
                    }

                    if ($this->record->users()->exists()) {
                        Notification::make()
                            ->warning()
                            ->title('Неможливо видалити')
                            ->body('Ця роль призначена користувачам. Спочатку зніми її з користувачів.')
                            ->send();

                        $action->halt();
                    }
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Роль видалено')
                        ->body('Роль успішно видалена.')
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
