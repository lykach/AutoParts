<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixProductPrimaryImages extends Command
{
    protected $signature = 'products:fix-primary-images {--dry-run : Тільки показати, без змін}';
    protected $description = 'Виставляє primary-зображення для товарів, де є фото, але не задано основне.';

    public function handle(): int
    {
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
            // 1) якщо primary нема — призначимо
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
                        // скидаємо primary на всяк випадок (якщо десь криво було)
                        ProductImage::query()
                            ->where('product_id', $productId)
                            ->update(['is_primary' => false]);

                        $first->forceFill(['is_primary' => true])->saveQuietly();
                    }

                    $fixed++;
                }
            }

            // 2) якщо primary більше одного — залишимо тільки “найперше”
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

        return self::SUCCESS;
    }
}
