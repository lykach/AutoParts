<?php

namespace App\Filament\Resources\CityCourierSlotExceptions\Pages;

use App\Filament\Resources\CityCourierSlotExceptions\CityCourierSlotExceptionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\UniqueConstraintViolationException;

class EditCityCourierSlotException extends EditRecord
{
    protected static string $resource = CityCourierSlotExceptionResource::class;

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (UniqueConstraintViolationException) {
            Notification::make()
                ->title('Дубль винятку')
                ->body('Для цього слота вже є виняток на цю дату.')
                ->warning()
                ->send();

            $this->addError('data.exception_date', 'Дубль: для цього слота вже є виняток на цю дату.');
        }
    }
}