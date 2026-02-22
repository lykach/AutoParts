<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
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
                            ->body("Категорія '{$record->name_uk}' має підкатегорії!")
                            ->persistent()
                            ->send();
                        
                        $action->cancel();
                        return;
                    }
                    
                    if ($record->hasProducts()) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка видалення')
                            ->body("Категорія '{$record->name_uk}' має товари!")
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
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Конвертуємо null parent_id → -1
        if (!isset($data['parent_id']) || $data['parent_id'] === null) {
            $data['parent_id'] = -1;
        }
        
        return $data;
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Конвертуємо -1 → null для відображення в формі
        if (isset($data['parent_id']) && $data['parent_id'] === -1) {
            $data['parent_id'] = null;
        }
        
        return $data;
    }
}