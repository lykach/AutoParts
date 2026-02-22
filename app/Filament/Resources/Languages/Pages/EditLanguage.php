<?php

namespace App\Filament\Resources\Languages\Pages;

use App\Filament\Resources\Languages\LanguageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditLanguage extends EditRecord
{
    protected static string $resource = LanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action) {
                    $record = $this->getRecord();

                    if ($record->is_default) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка видалення')
                            ->body('Неможливо видалити головну мову сайту!')
                            ->send();

                        $action->cancel();
                    }
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Мову видалено')
                        ->body('Мова успішно видалена з системи.')
                ),
        ];
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->success()
            ->title('Мову оновлено')
            ->body('Зміни успішно збережено.')
            ->send();
    }
}
