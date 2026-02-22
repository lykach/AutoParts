<?php
namespace App\Filament\Resources\Languages\Pages;

use App\Filament\Resources\Languages\LanguageResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateLanguage extends CreateRecord
{
    protected static string $resource = LanguageResource::class;
    
    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->title('Мову створено')
            ->body('Мова успішно додана до системи.')
            ->send();
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}