<?php

namespace App\Filament\Resources\StockSource\Pages;

use App\Filament\Resources\StockSource\StockSourceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStockSource extends EditRecord
{
    protected static string $resource = StockSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn () => $this->record?->storeLinks()->exists())
                ->tooltip(fn () => $this->record?->storeLinks()->exists()
                    ? 'Неможливо видалити: джерело використовується в магазинах.'
                    : null
                ),
        ];
    }
}
