<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductImage extends Model
{
    protected $table = 'product_images';

    protected $fillable = [
        'product_id',
        'source',
        'image_path',
        'external_url',
        'title',
        'alt',
        'is_primary',
        'sort_order',
        'is_active',
        'convert_to_webp',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'convert_to_webp' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute(): ?string
    {
        if ($this->source === 'url' && $this->external_url) {
            return $this->external_url;
        }

        if ($this->image_path) {
            return asset('storage/' . ltrim($this->image_path, '/'));
        }

        return null;
    }

    /* ============================================= */

    protected static function booted(): void
    {
        static::saving(function (self $img) {

            $img->title = filled($img->title) ? trim($img->title) : null;
            $img->alt   = filled($img->alt) ? trim($img->alt) : null;

            $img->source = $img->source ?? (
                filled($img->external_url) ? 'url' : 'upload'
            );

            $img->is_active ??= true;
            $img->convert_to_webp ??= true;

            $hasFile = filled($img->image_path);
            $hasUrl  = filled($img->external_url);

            if (! $hasFile && ! $hasUrl) {
                throw ValidationException::withMessages([
                    'image_path' => 'Потрібно завантажити зображення або вказати URL.',
                ]);
            }

            if ($hasUrl) {
                $img->source = 'url';
                $img->image_path = null;
                $img->convert_to_webp = false; // URL не конвертуємо
            }

            if ($hasFile) {
                $img->source = 'upload';
                $img->external_url = null;
            }

            /* ---------- AUTO SORT ---------- */
            if (empty($img->sort_order) || $img->sort_order <= 0) {
                $max = static::where('product_id', $img->product_id)
                    ->where('id', '!=', $img->id ?? 0)
                    ->max('sort_order') ?? 0;

                $img->sort_order = $max + 1;
            }

            /* ---------- AUTO ALT ---------- */
            if (empty($img->alt)) {
                $p = $img->product()
                    ->with([
                        'manufacturer:id,name',
                        'translations' => fn ($t) => $t->where('locale', 'uk'),
                    ])
                    ->first();

                if ($p) {
                    $brand = $p->manufacturer?->name ?? null;
                    $article = $p->article_raw ?? null;
                    $nameUk = $p->translations->first()?->name ?? null;

                    $alt = trim(implode(' ', array_filter([$brand, $article])));
                    if ($nameUk) {
                        $alt .= ' — ' . $nameUk;
                    }

                    $img->alt = $alt ?: null;
                }
            }

            /* ---------- PRIMARY ---------- */
            if (! $img->is_active) {
                $img->is_primary = false;
            }

            if ($img->is_primary) {
                static::where('product_id', $img->product_id)
                    ->where('id', '!=', $img->id ?? 0)
                    ->update(['is_primary' => false]);
            }
        });

        static::saved(fn ($img) => self::stabilize($img->product_id));
        static::deleted(fn ($img) => self::stabilize($img->product_id));

        static::saved(function (self $img) {
            if ($img->source === 'upload') {
                $img->convertToWebpIfNeeded();
            }
        });
    }

    /* ============================================= */

    public static function stabilize(int $productId): void
    {
        DB::transaction(function () use ($productId) {

            $images = static::where('product_id', $productId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $i = 1;
            foreach ($images as $img) {
                if ($img->sort_order != $i) {
                    $img->updateQuietly(['sort_order' => $i]);
                }
                $i++;
            }

            $hasPrimary = static::where('product_id', $productId)
                ->where('is_active', true)
                ->where('is_primary', true)
                ->exists();

            if (! $hasPrimary) {
                $first = static::where('product_id', $productId)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->first();

                if ($first) {
                    static::where('product_id', $productId)
                        ->update(['is_primary' => false]);

                    $first->updateQuietly(['is_primary' => true]);
                }
            }
        });
    }

    /* ============================================= */

    private function convertToWebpIfNeeded(): void
    {
        if (! $this->convert_to_webp) return;
        if (! self::webpSupported()) return;
        if (! $this->image_path) return;

        $disk = Storage::disk('public');

        if (! $disk->exists($this->image_path)) return;

        $full = $disk->path($this->image_path);
        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));

        if (! in_array($ext, ['jpg','jpeg','png'])) return;

        try {
            $img = $ext === 'png'
                ? imagecreatefrompng($full)
                : imagecreatefromjpeg($full);

            if (! $img) return;

            if ($ext === 'png') {
                imagepalettetotruecolor($img);
                imagesavealpha($img, true);
            }

            $newPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $this->image_path);
            $webpFull = $disk->path($newPath);

            imagewebp($img, $webpFull, 82);
            imagedestroy($img);

            if ($disk->exists($newPath)) {
                $disk->delete($this->image_path);
                $this->updateQuietly(['image_path' => $newPath]);
            }

        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function webpSupported(): bool
    {
        return extension_loaded('gd') &&
               function_exists('imagewebp');
    }
}
