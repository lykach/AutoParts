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

        // ✅ renamed
        'multiplicity',

        'availability_status',

        'price_purchase',
        'price_sell',
        'currency',

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

        'multiplicity' => 'integer',
        'availability_status' => 'string',

        'price_purchase' => 'decimal:2',
        'price_sell' => 'decimal:2',
        'currency' => 'string',

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

    public function getAvailableQtyAttribute(): float
    {
        $qty = (float) ($this->qty ?? 0);
        $res = (float) ($this->reserved_qty ?? 0);

        return max(0, $qty - $res);
    }

    public function getAvailableForSaleQtyAttribute(): float
    {
        $available = $this->available_qty;
        $m = (int) ($this->multiplicity ?? 1);

        if ($m <= 1) {
            return $available;
        }

        $eps = 0.00001;
        $packsCount = (int) floor(($available + $eps) / $m);

        return max(0, $packsCount * $m);
    }

    // -------------------------
    // Currency helpers
    // -------------------------
    private static array $rateCache = [];

    public static function rateToUah(?string $code): float
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') $code = 'UAH';

        if (isset(self::$rateCache[$code])) {
            return self::$rateCache[$code];
        }

        $rate = (float) (Currency::query()
            ->where('code', $code)
            ->value('rate') ?? 1.0);

        if ($rate <= 0) $rate = 1.0;

        return self::$rateCache[$code] = $rate;
    }

    public function getPriceSellUahAttribute(): ?float
    {
        if ($this->price_sell === null) return null;
        $rate = self::rateToUah($this->currency);

        return round(((float) $this->price_sell) * $rate, 2);
    }

    public function getPricePurchaseUahAttribute(): ?float
    {
        if ($this->price_purchase === null) return null;
        $rate = self::rateToUah($this->currency);

        return round(((float) $this->price_purchase) * $rate, 2);
    }
}