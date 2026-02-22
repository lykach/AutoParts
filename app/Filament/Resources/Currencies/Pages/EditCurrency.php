<?php
namespace App\Filament\Resources\Currencies\Pages;

use App\Filament\Resources\Currencies\CurrencyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCurrency extends EditRecord
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
            
            DeleteAction::make()
                ->before(function (DeleteAction $action, $record) {
                    if ($record->is_default) {
                        Notification::make()
                            ->danger()
                            ->title('Помилка видалення')
                            ->body('Неможливо видалити головну валюту магазину!')
                            ->persistent()
                            ->send();
                        
                        $action->cancel();
                    }
                })
                ->successRedirectUrl(static::getResource()::getUrl('index')),
        ];
    }
    
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Валюту оновлено')
            ->body('Зміни успішно збережено.');
    }
}