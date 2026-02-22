<?php
namespace App\Filament\Resources\Currencies\Pages;

use App\Filament\Resources\Currencies\CurrencyResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCurrency extends CreateRecord
{
    protected static string $resource = CurrencyResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Назад до списку')
                ->url(static::getResource()::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
        ];
    }
    
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Валюту створено')
            ->body('Валюта успішно додана до системи.');
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}