<?php
namespace App\Filament\Resources\CategoryMirrors\Pages;

use App\Filament\Resources\CategoryMirrors\CategoryMirrorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCategoryMirror extends EditRecord
{
    protected static string $resource = CategoryMirrorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Дублікат видалено')
                        ->body('Дублікат категорії успішно видалено.')
                ),
        ];
    }
    
    protected function afterSave(): void
    {
        Notification::make()
            ->success()
            ->title('Дублікат оновлено')
            ->body('Зміни успішно збережено.')
            ->send();
    }
}