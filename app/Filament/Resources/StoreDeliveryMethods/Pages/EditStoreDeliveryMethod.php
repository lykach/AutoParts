<?php

namespace App\Filament\Resources\StoreDeliveryMethods\Pages;

use App\Filament\Resources\StoreDeliveryMethods\StoreDeliveryMethodResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\UniqueConstraintViolationException;

class EditStoreDeliveryMethod extends EditRecord
{
    protected static string $resource = StoreDeliveryMethodResource::class;

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (UniqueConstraintViolationException) {
            Notification::make()
                ->title('Дубль доставки')
                ->body('Цей спосіб доставки вже підключено до цього магазину.')
                ->warning()
                ->send();

            $this->addError('data.delivery_method_id', 'Дубль: цей спосіб доставки вже існує для цього магазину.');
        }
    }
}