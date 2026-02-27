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
                ->disabled(fn () =>
                    $this->record?->storeLinks()->exists()
                    || $this->record?->locations()->exists()
                    || $this->record?->items()->exists()
                )
                ->tooltip(fn () => (
                    $this->record?->storeLinks()->exists()
                        ? 'Неможливо видалити: джерело використовується в магазинах.'
                        : ($this->record?->locations()->exists()
                            ? 'Неможливо видалити: у джерела є склади/локації.'
                            : ($this->record?->items()->exists()
                                ? 'Неможливо видалити: у джерелі є залишки (stock_items).'
                                : null
                            )
                        )
                )),
        ];
    }
}