<?php

namespace App\Filament\Resources\StoreStockSources\Pages;

use App\Filament\Resources\StoreStockSources\StoreStockSourceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\UniqueConstraintViolationException;

class CreateStoreStockSource extends CreateRecord
{
    protected static string $resource = StoreStockSourceResource::class;

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (UniqueConstraintViolationException) {
            Notification::make()
                ->title('Склад уже підключений')
                ->body('Цю локацію вже підключено до вибраного магазину. Обери іншу локацію.')
                ->warning()
                ->send();

            $this->addError('data.stock_source_location_id', 'Дубль: ця локація вже існує для цього магазину.');
        }
    }
}