<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Назад до списку')
                ->url(static::getResource()::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),

            DeleteAction::make()
                ->before(function (DeleteAction $action) {
                    $record = $this->getRecord();

                    if ($record->children()->exists()) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка видалення')
                            ->body("Категорія '{$record->name_uk}' має підкатегорії.")
                            ->persistent()
                            ->send();

                        $action->cancel();
                        return;
                    }

                    if ($record->hasProducts()) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка видалення')
                            ->body("Категорія '{$record->name_uk}' має товари.")
                            ->persistent()
                            ->send();

                        $action->cancel();
                        return;
                    }

                    if ($record->hasCharacteristics()) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка видалення')
                            ->body("Категорія '{$record->name_uk}' має характеристики.")
                            ->persistent()
                            ->send();

                        $action->cancel();
                        return;
                    }

                    if ($record->mirrorsAsParent()->exists()) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка видалення')
                            ->body("Категорія '{$record->name_uk}' використовується як контейнер для дзеркал.")
                            ->persistent()
                            ->send();

                        $action->cancel();
                        return;
                    }

                    if ($record->mirrorsAsSource()->exists()) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка видалення')
                            ->body("Категорія '{$record->name_uk}' використовується як джерело для дзеркал.")
                            ->persistent()
                            ->send();

                        $action->cancel();
                        return;
                    }
                })
                ->successRedirectUrl(static::getResource()::getUrl('index')),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Категорію оновлено')
            ->body('Зміни успішно збережено.');
    }
}