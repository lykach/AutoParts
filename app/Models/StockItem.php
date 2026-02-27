<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockItem extends Model
{
    protected $fillable = [
        'stock_source_id',
        'stock_source_location_id',
        'product_id',

        'qty',
        'reserved_qty',
        'available_qty',
        'sellable_qty',

        'multiplicity',
        'availability_status',

        'price_purchase',
        'price_purchase_uah',
        'price_sell',
        'price_sell_uah',
        'currency',

        // ✅ нові
        'delivery_unit', // days|hours|null(inherit)
        'delivery_min',
        'delivery_max',

        // legacy (не чіпаємо, але UI не використовуємо)
        'delivery_days_min',
        'delivery_days_max',

        'source_updated_at',
        'meta',
    ];

    protected $casts = [
        'stock_source_id' => 'integer',
        'stock_source_location_id' => 'integer',
        'product_id' => 'integer',

        'qty' => 'decimal:3',
        'reserved_qty' => 'decimal:3',
        'available_qty' => 'decimal:3',
        'sellable_qty' => 'decimal:3',

        'multiplicity' => 'integer',
        'availability_status' => 'string',

        'price_purchase' => 'decimal:2',
        'price_purchase_uah' => 'decimal:2',
        'price_sell' => 'decimal:2',
        'price_sell_uah' => 'decimal:2',
        'currency' => 'string',

        'delivery_unit' => 'string',
        'delivery_min' => 'integer',
        'delivery_max' => 'integer',

        'delivery_days_min' => 'integer',
        'delivery_days_max' => 'integer',

        'source_updated_at' => 'datetime',
        'meta' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(StockSource::class, 'stock_source_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockSourceLocation::class, 'stock_source_location_id');
    }

    public static function availabilityOptions(): array
    {
        return [
            'in_stock'     => 'В наявності',
            'on_order'     => 'Під замовлення',
            'expected'     => 'Очікується',
            'backorder'    => 'Backorder',
            'discontinued' => 'Знято з виробництва',
        ];
    }

    public static function deliveryUnitOptions(): array
    {
        return [
            'days' => 'Дні',
            'hours' => 'Години',
        ];
    }

    private static array $rateCache = [];

    public static function rateToUah(?string $code): float
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') $code = 'UAH';

        if (isset(self::$rateCache[$code])) return self::$rateCache[$code];

        $rate = (float) (Currency::query()
            ->where('code', $code)
            ->value('rate') ?? 1.0);

        if ($rate <= 0) $rate = 1.0;

        return self::$rateCache[$code] = $rate;
    }

    protected static function booted(): void
    {
        static::saving(function (self $i) {
            // -------- qty/available/sellable --------
            $qty = (float) ($i->qty ?? 0);
            $res = (float) ($i->reserved_qty ?? 0);

            $available = max(0, $qty - $res);
            $i->available_qty = round($available, 3);

            $m = (int) ($i->multiplicity ?? 1);
            if ($m <= 1) {
                $sellable = $available;
            } else {
                $eps = 0.00001;
                $packs = (int) floor(($available + $eps) / $m);
                $sellable = max(0, $packs * $m);
            }
            $i->sellable_qty = round($sellable, 3);

            // -------- currency + uah --------
            $cur = strtoupper(trim((string) ($i->currency ?? 'UAH')));
            if ($cur === '') $cur = 'UAH';
            $i->currency = $cur;

            $rate = self::rateToUah($cur);

            $i->price_purchase_uah = $i->price_purchase === null ? null : round(((float) $i->price_purchase) * $rate, 2);
            $i->price_sell_uah     = $i->price_sell === null ? null : round(((float) $i->price_sell) * $rate, 2);

            // -------- delivery defaults/override --------
            $i->delivery_min = filled($i->delivery_min) ? (int) $i->delivery_min : null;
            $i->delivery_max = filled($i->delivery_max) ? (int) $i->delivery_max : null;

            if (filled($i->delivery_unit)) {
                $u = strtolower(trim((string) $i->delivery_unit));
                $i->delivery_unit = in_array($u, ['days', 'hours'], true) ? $u : null;
            } else {
                $i->delivery_unit = null;
            }

            if ($i->delivery_min !== null && $i->delivery_min < 0) $i->delivery_min = 0;
            if ($i->delivery_max !== null && $i->delivery_max < 0) $i->delivery_max = 0;

            if ($i->delivery_min !== null && $i->delivery_max !== null && $i->delivery_min > $i->delivery_max) {
                [$i->delivery_min, $i->delivery_max] = [$i->delivery_max, $i->delivery_min];
            }

            // якщо не задано — наслідуємо зі складу (а він уже наслідує з джерела)
            if (($i->delivery_unit === null) || ($i->delivery_min === null && $i->delivery_max === null)) {
                $loc = StockSourceLocation::query()
                    ->select(['id', 'delivery_unit', 'delivery_min', 'delivery_max', 'stock_source_id'])
                    ->whereKey($i->stock_source_location_id)
                    ->first();

                if ($i->delivery_unit === null) {
                    $i->delivery_unit = $loc?->delivery_unit ?: null;
                }

                if ($i->delivery_min === null && $i->delivery_max === null) {
                    $i->delivery_min = $loc?->delivery_min;
                    $i->delivery_max = $loc?->delivery_max;
                }

                // fallback ще на джерело, якщо у локації пусто
                if (($i->delivery_unit === null) || ($i->delivery_min === null && $i->delivery_max === null)) {
                    $srcId = $i->stock_source_id ?: $loc?->stock_source_id;
                    if ($srcId) {
                        $src = StockSource::query()
                            ->select(['id', 'delivery_unit', 'delivery_min', 'delivery_max'])
                            ->whereKey($srcId)
                            ->first();

                        if ($i->delivery_unit === null) $i->delivery_unit = $src?->delivery_unit ?: 'days';
                        if ($i->delivery_min === null && $i->delivery_max === null) {
                            $i->delivery_min = $src?->delivery_min;
                            $i->delivery_max = $src?->delivery_max;
                        }
                    } else {
                        if ($i->delivery_unit === null) $i->delivery_unit = 'days';
                    }
                }
            }
        });
    }

    public function formatDelivery(): string
    {
        $u = $this->delivery_unit ?: 'days';
        $suffix = $u === 'hours' ? 'год.' : 'дн.';

        $min = $this->delivery_min;
        $max = $this->delivery_max;

        if ($min === null && $max === null) return '—';
        if ($min !== null && $max !== null) return "{$min}-{$max} {$suffix}";
        if ($min !== null) return "від {$min} {$suffix}";
        return "до {$max} {$suffix}";
    }
}