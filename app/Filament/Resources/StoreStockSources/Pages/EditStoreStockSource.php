<?php

namespace App\Filament\Resources\StoreStockSources\Pages;

use App\Filament\Resources\StoreStockSources\StoreStockSourceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\UniqueConstraintViolationException;

class EditStoreStockSource extends EditRecord
{
    protected static string $resource = StoreStockSourceResource::class;

    /**
     * ✅ Filament v5 signature:
     * save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
     */
    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (UniqueConstraintViolationException) {
            Notification::make()
                ->title('Дубль складу')
                ->body('Цю локацію вже підключено до цього магазину. Обери іншу.')
                ->warning()
                ->send();

            $this->addError('data.stock_source_location_id', 'Дубль: ця локація вже існує для цього магазину.');
        }
    }
}