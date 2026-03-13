<?php

namespace App\Filament\Resources\CityCourierSlotExceptions\Pages;

use App\Filament\Resources\CityCourierSlotExceptions\CityCourierSlotExceptionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\UniqueConstraintViolationException;

class CreateCityCourierSlotException extends CreateRecord
{
    protected static string $resource = CityCourierSlotExceptionResource::class;

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (UniqueConstraintViolationException) {
            Notification::make()
                ->title('Виняток уже існує')
                ->body('Для цього слота вже є виняток на цю дату.')
                ->warning()
                ->send();

            $this->addError('data.exception_date', 'Дубль: для цього слота вже є виняток на цю дату.');
        }
    }
}