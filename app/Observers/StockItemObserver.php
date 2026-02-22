<?php

namespace App\Observers;

use App\Models\StockItem;

class StockItemObserver
{
    public function saved(StockItem $item): void
    {
        $item->product?->recalcBestOffer();
    }

    public function deleted(StockItem $item): void
    {
        $item->product?->recalcBestOffer();
    }

    public function restored(StockItem $item): void
    {
        $item->product?->recalcBestOffer();
    }
}
