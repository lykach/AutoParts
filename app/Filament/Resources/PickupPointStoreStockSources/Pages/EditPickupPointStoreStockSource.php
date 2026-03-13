<?php

namespace App\Filament\Resources\PickupPointStoreStockSources\Pages;

use App\Filament\Resources\PickupPointStoreStockSources\PickupPointStoreStockSourceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\UniqueConstraintViolationException;

class EditPickupPointStoreStockSource extends EditRecord
{
    protected static string $resource = PickupPointStoreStockSourceResource::class;

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (UniqueConstraintViolationException) {
            Notification::make()
                ->title('Дубль джерела')
                ->body('Цей склад магазину вже підключено до цієї точки самовивозу.')
                ->warning()
                ->send();

            $this->addError('data.store_stock_source_id', 'Дубль: це джерело вже існує для цієї точки самовивозу.');
        }
    }
}