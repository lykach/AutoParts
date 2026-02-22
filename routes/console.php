<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Models\Product;
use App\Models\ProductImage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ✅ Перерахунок cached best offer по всіх товарах
Artisan::command('products:recalc-best-offer {--chunk=200}', function () {
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
})->purpose('Recalculate cached best offer fields for all products');

// ✅ Фікс primary-зображень (якщо фото додали напряму в БД або імпортом)
Artisan::command('products:fix-primary-images {--dry-run : Тільки показати, без змін}', function () {
    $dry = (bool) $this->option('dry-run');

    // product_id, де є активні фото
    $productIds = ProductImage::query()
        ->where('is_active', true)
        ->select('product_id')
        ->groupBy('product_id')
        ->pluck('product_id');

    $fixed = 0;
    $duplicatesFixed = 0;

    foreach ($productIds as $productId) {
        // 1) Якщо primary нема — призначимо
        $hasPrimary = ProductImage::query()
            ->where('product_id', $productId)
            ->where('is_primary', true)
            ->exists();

        if (! $hasPrimary) {
            $first = ProductImage::query()
                ->where('product_id', $productId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();

            if ($first) {
                if ($dry) {
                    $this->line("DRY: product_id={$productId} -> set primary image_id={$first->id}");
                } else {
                    ProductImage::query()
                        ->where('product_id', $productId)
                        ->update(['is_primary' => false]);

                    $first->forceFill(['is_primary' => true])->saveQuietly();
                }

                $fixed++;
            }
        }

        // 2) Якщо primary більше одного — залишимо тільки найперше
        $primaryIds = ProductImage::query()
            ->where('product_id', $productId)
            ->where('is_primary', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id');

        if ($primaryIds->count() > 1) {
            $keepId = (int) $primaryIds->first();
            $dropIds = $primaryIds->slice(1)->all();

            if ($dry) {
                $this->line("DRY: product_id={$productId} -> keep primary={$keepId}, drop=" . implode(',', $dropIds));
            } else {
                ProductImage::query()
                    ->where('product_id', $productId)
                    ->whereIn('id', $dropIds)
                    ->update(['is_primary' => false]);
            }

            $duplicatesFixed++;
        }
    }

    $this->info("Готово. Призначено primary: {$fixed}. Виправлено дублі primary: {$duplicatesFixed}.");
})->purpose('Fix primary images for products');

// ✅ Автоматичне оновлення курсів валют щодня о 9:00
Schedule::command('currency:update')
    ->dailyAt('09:00')
    ->onSuccess(fn () => \Log::info('Курси валют автоматично оновлено'))
    ->onFailure(fn () => \Log::error('Помилка автоматичного оновлення курсів'));

// ✅ Фікс primary-зображень щодня
Schedule::command('products:fix-primary-images')
    ->dailyAt('03:15')
    ->onSuccess(fn () => \Log::info('Primary images fixed'))
    ->onFailure(fn () => \Log::error('Primary images fix failed'));
