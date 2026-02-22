<?php

namespace App\Filament\Resources\UserGroups\Pages;

use App\Filament\Resources\UserGroups\UserGroupResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUserGroup extends EditRecord
{
    protected static string $resource = UserGroupResource::class;

    protected function afterSave(): void
    {
        Notification::make()
            ->success()
            ->title('Зміни збережено')
            ->body("Група '{$this->record->name}' успішно оновлена.")
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Видалити')
                ->requiresConfirmation()

                // ✅ Ховаємо кнопку, якщо є користувачі
                ->visible(fn () => ! $this->record->users()->exists())

                // ✅ І на всяк випадок блокуємо видалення, якщо хтось відкрив стару вкладку
                ->before(function (Actions\DeleteAction $action) {
                    if ($this->record->users()->exists()) {
                        Notification::make()
                            ->warning()
                            ->title('Неможливо видалити')
                            ->body('Ця група містить активних користувачів. Спочатку переведіть їх в іншу групу.')
                            ->send();

                        $action->halt();
                    }
                })

                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Групу видалено')
                        ->body('Група успішно видалена.')
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
