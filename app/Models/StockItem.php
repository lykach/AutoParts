<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockItem extends Model
{
    protected $fillable = [
        'stock_source_id',
        'product_id',

        'qty',
        'reserved_qty',

        'pack_qty',
        'availability_status',

        'min_order_qty',

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
        'product_id' => 'integer',

        'qty' => 'decimal:3',
        'reserved_qty' => 'decimal:3',

        'pack_qty' => 'integer',
        'availability_status' => 'string',

        'min_order_qty' => 'integer',

        'price_purchase' => 'decimal:2',
        'price_sell' => 'decimal:2',
        'currency' => 'string',

        'delivery_days_min' => 'integer',
        'delivery_days_max' => 'integer',

        'source_updated_at' => 'datetime',
        'meta' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function source()
    {
        return $this->belongsTo(StockSource::class, 'stock_source_id');
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
        $pack = (int) ($this->pack_qty ?? 1);

        if ($pack <= 1) {
            return $available;
        }

        $eps = 0.00001;
        $packsCount = (int) floor(($available + $eps) / $pack);

        return max(0, $packsCount * $pack);
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
