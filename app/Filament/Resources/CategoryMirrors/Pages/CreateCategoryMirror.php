<?php
namespace App\Filament\Resources\CategoryMirrors\Pages;

use App\Filament\Resources\CategoryMirrors\CategoryMirrorResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCategoryMirror extends CreateRecord
{
    protected static string $resource = CategoryMirrorResource::class;
    
    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->title('Дублікат створено')
            ->body('Дублікат категорії успішно створено.')
            ->send();
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    // Обробка помилок валідації
    protected function onValidationError(\Illuminate\Validation\ValidationException $exception): void
    {
        Notification::make()
            ->danger()
            ->title('Помилка створення')
            ->body($exception->getMessage())
            ->persistent()
            ->send();
    }
}