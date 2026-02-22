<?php

namespace App\Filament\Resources\Permissions\Pages;

use App\Filament\Resources\Permissions\PermissionResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPermission extends EditRecord
{
    protected static string $resource = PermissionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // guard завжди web
        $data['guard_name'] = 'web';

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()

                // кнопка delete тільки для super-admin або permissions.delete
                ->visible(fn () => auth()->user()?->hasRole('super-admin') || auth()->user()?->can('permissions.delete'))

                // страховка: якщо permission вже прив’язаний до ролей — стоп
                ->before(function (Actions\DeleteAction $action) {
                    if (! $this->record) {
                        $action->halt();
                        return;
                    }

                    if ($this->record->roles()->exists()) {
                        Notification::make()
                            ->warning()
                            ->title('Неможливо видалити')
                            ->body('Це право використовується в ролях. Спочатку прибери його з ролей.')
                            ->send();

                        $action->halt();
                    }
                })

                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Permission видалено')
                        ->body('Право доступу успішно видалено.')
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Зміни збережено')
            ->body("Permission '{$this->record->name}' оновлено.");
    }
}
