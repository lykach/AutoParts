<?php

namespace App\Filament\Resources\StoreDeliveryMethods\Pages;

use App\Filament\Resources\StoreDeliveryMethods\StoreDeliveryMethodResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\UniqueConstraintViolationException;

class CreateStoreDeliveryMethod extends CreateRecord
{
    protected static string $resource = StoreDeliveryMethodResource::class;

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (UniqueConstraintViolationException) {
            Notification::make()
                ->title('Доставка вже підключена')
                ->body('Цей спосіб доставки вже підключено до вибраного магазину.')
                ->warning()
                ->send();

            $this->addError('data.delivery_method_id', 'Дубль: цей спосіб доставки вже існує для цього магазину.');
        }
    }
}