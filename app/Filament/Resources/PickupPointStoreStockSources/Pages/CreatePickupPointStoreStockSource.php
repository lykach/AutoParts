<?php

namespace App\Filament\Resources\PickupPointStoreStockSources\Pages;

use App\Filament\Resources\PickupPointStoreStockSources\PickupPointStoreStockSourceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\UniqueConstraintViolationException;

class CreatePickupPointStoreStockSource extends CreateRecord
{
    protected static string $resource = PickupPointStoreStockSourceResource::class;

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (UniqueConstraintViolationException) {
            Notification::make()
                ->title('Джерело вже підключене')
                ->body('Цей склад магазину вже підключено до вибраної точки самовивозу.')
                ->warning()
                ->send();

            $this->addError('data.store_stock_source_id', 'Дубль: це джерело вже існує для цієї точки самовивозу.');
        }
    }
}