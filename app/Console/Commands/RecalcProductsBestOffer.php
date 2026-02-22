<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class RecalcProductsBestOffer extends Command
{
    protected $signature = 'products:recalc-best-offer {--chunk=200}';
    protected $description = 'Recalculate cached best offer fields for all products.';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');
        if ($chunk <= 0) {
            $chunk = 200;
        }

        $this->info("Recalculating best offer for products (chunk={$chunk})...");

        Product::query()
            ->select('id')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) {
                $ids = $rows->pluck('id')->all();
                $products = Product::query()->whereIn('id', $ids)->get();

                foreach ($products as $p) {
                    $p->recalcBestOffer();
                }
            });

        $this->info('Done.');
        return self::SUCCESS;
    }
}
