<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'manufacturer_id',

        'article_raw',
        'article_norm',

        'is_active',

        'created_source',
        'created_by',

        'tecdoc_id',

        'uuid',

        // ✅ Доставка / габарити
        'weight_kg',
        'length_cm',
        'width_cm',
        'height_cm',

        // ✅ best offer
        'best_price_uah',
        'best_price_original',
        'best_currency_code',
        'best_stock_source_id',
        'best_stock_qty',

        // ✅ нове best delivery (days/hours)
        'best_delivery_unit',
        'best_delivery_min',
        'best_delivery_max',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'manufacturer_id' => 'integer',
        'is_active' => 'boolean',

        'created_by' => 'integer',
        'tecdoc_id' => 'integer',

        'uuid' => 'string',

        'weight_kg' => 'decimal:3',
        'length_cm' => 'decimal:1',
        'width_cm'  => 'decimal:1',
        'height_cm' => 'decimal:1',

        'best_price_uah' => 'decimal:2',
        'best_price_original' => 'decimal:2',
        'best_stock_source_id' => 'integer',
        'best_stock_qty' => 'decimal:3',

        'best_delivery_unit' => 'string',
        'best_delivery_min' => 'integer',
        'best_delivery_max' => 'integer',
    ];

    // -------------------
    // Scopes
    // -------------------
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    // -------------------
    // Relations
    // -------------------
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
    }

    public function translations()
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function translation(string $locale = null): ?ProductTranslation
    {
        $locale = $locale ?: app()->getLocale();

        return $this->translations->firstWhere('locale', $locale)
            ?: $this->translations->firstWhere('locale', 'uk')
            ?: $this->translations->first();
    }

    public function stockItems()
    {
        return $this->hasMany(StockItem::class);
    }

    public function bestSource()
    {
        return $this->belongsTo(StockSource::class, 'best_stock_source_id');
    }

    public function oemNumbers()
    {
        return $this->hasMany(ProductOemNumber::class);
    }

    public function components()
    {
        return $this->hasMany(ProductComponent::class)->orderBy('position');
    }

    public function componentsCount(): int
    {
        return $this->components()->count();
    }

    public function barcodes()
    {
        return $this->hasMany(ProductBarcode::class);
    }

    public function primaryBarcode()
    {
        return $this->hasOne(ProductBarcode::class)->where('is_primary', true);
    }

    public function files()
    {
        return $this->hasMany(ProductFile::class)->orderBy('sort_order');
    }

    public function primaryFile()
    {
        return $this->hasOne(ProductFile::class)->where('is_primary', true);
    }

    public function relatedLinks()
    {
        return $this->hasMany(ProductRelated::class, 'product_id')
            ->orderBy('sort_order');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function details(): HasMany
    {
        return $this->hasMany(ProductDetail::class)->orderBy('sort');
    }

    public function characteristics(): HasMany
    {
        return $this->hasMany(ProductCharacteristic::class)->orderBy('sort');
    }

    // -------------------
    // Normalization
    // -------------------
    public static function normalizeArticle(?string $article): string
    {
        $s = trim((string) $article);
        if ($s === '') return '';

        $s = mb_strtoupper($s, 'UTF-8');
        $s = preg_replace('/[\s\-\.\_\/\\\\]+/u', '', $s) ?? $s;

        $map = [
            'А' => 'A', 'В' => 'B', 'Е' => 'E', 'К' => 'K', 'М' => 'M',
            'Н' => 'H', 'О' => 'O', 'Р' => 'P', 'С' => 'C', 'Т' => 'T',
            'Х' => 'X', 'У' => 'Y', 'І' => 'I',
            'Ё' => 'E',
        ];
        $s = strtr($s, $map);

        $s = preg_replace('/[^\p{L}\p{N}]+/u', '', $s) ?? $s;

        return $s;
    }

    public static function buildDefaultSlug(?Manufacturer $manufacturer, ?string $articleNorm): string
    {
        $brand = $manufacturer?->slug ?: ($manufacturer?->name ? Str::slug($manufacturer->name) : '');
        $art = strtolower(trim((string) $articleNorm));

        $base = trim($brand . '-' . $art, '-');
        $base = $base !== '' ? $base : Str::random(10);

        return Str::slug($base);
    }

    protected static function booted(): void
    {
        static::saving(function (self $p) {
            if ($p->article_raw !== null) {
                $p->article_raw = mb_strtoupper((string) $p->article_raw, 'UTF-8');
            }

            if (!empty($p->article_raw) && (empty($p->article_norm) || $p->isDirty('article_raw'))) {
                $p->article_norm = self::normalizeArticle($p->article_raw);
            }

            if ($p->category_id) {
                $ok = Category::query()
                    ->whereKey($p->category_id)
                    ->where('is_active', 1)
                    ->where('is_leaf', 1)
                    ->where('is_container', 0)
                    ->exists();

                if (! $ok) {
                    throw ValidationException::withMessages([
                        'category_id' => 'Товар можна додавати тільки в активну кінцеву (leaf) категорію (не контейнер).',
                    ]);
                }
            }

            foreach (['weight_kg', 'length_cm', 'width_cm', 'height_cm'] as $f) {
                if ($p->{$f} !== null && (float) $p->{$f} < 0) {
                    throw ValidationException::withMessages([
                        $f => 'Значення не може бути від’ємним.',
                    ]);
                }
            }
        });
    }

    // -------------------
    // Best offer recalculation
    // -------------------
    public function recalcBestOffer(): void
    {
        $items = $this->stockItems()
            ->whereNotNull('price_sell_uah') // вже перераховано в БД
            ->where('availability_status', '!=', 'discontinued')
            ->get();

        $best = null;
        $bestUah = null;

        foreach ($items as $it) {
            if ((float) ($it->sellable_qty ?? 0) <= 0) {
                continue;
            }

            $uah = $it->price_sell_uah;
            if ($uah === null) continue;

            if ($bestUah === null || (float) $uah < $bestUah) {
                $bestUah = (float) $uah;
                $best = $it;
            }
        }

        if (! $best) {
            $this->forceFill([
                'best_price_uah' => null,
                'best_price_original' => null,
                'best_currency_code' => null,
                'best_stock_source_id' => null,
                'best_stock_qty' => null,
                'best_delivery_unit' => null,
                'best_delivery_min' => null,
                'best_delivery_max' => null,
            ])->saveQuietly();

            return;
        }

        $this->forceFill([
            'best_price_uah' => $best->price_sell_uah,
            'best_price_original' => $best->price_sell,
            'best_currency_code' => strtoupper((string) ($best->currency ?? 'UAH')),
            'best_stock_source_id' => $best->stock_source_id,
            'best_stock_qty' => $best->sellable_qty,

            'best_delivery_unit' => $best->delivery_unit ?: 'days',
            'best_delivery_min' => $best->delivery_min,
            'best_delivery_max' => $best->delivery_max,
        ])->saveQuietly();
    }

    // -------------------
    // Computed / Helpers
    // -------------------
    public function getDisplayNameAttribute(): string
    {
        return (string) ($this->translation()?->name ?? $this->article_raw ?? 'Без назви');
    }

    public function getMpnAttribute(): string
    {
        return (string) ($this->article_norm ?? '');
    }

    public function getSkuAttribute(): string
    {
        return (string) ($this->article_raw ?? '');
    }
}