<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreStockSource extends Model
{
    protected $table = 'store_stock_sources';

    protected $fillable = [
        'store_id',
        'stock_source_id',
        'is_active',
        'priority',

        // ✅ new
        'markup_percent',
        'min_delivery_days',
        'max_delivery_days',

        // existing
        'lead_time_days',
        'cutoff_time',
        'pickup_available',
        'price_multiplier',
        'extra_fee',
        'min_order_amount',
        'coverage',
        'settings',
        'note',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',

        // ✅ new
        'markup_percent' => 'decimal:2',
        'min_delivery_days' => 'integer',
        'max_delivery_days' => 'integer',

        // existing
        'lead_time_days' => 'integer',
        'pickup_available' => 'boolean',
        'price_multiplier' => 'decimal:4',
        'extra_fee' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'coverage' => 'array',
        'settings' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function stockSource(): BelongsTo
    {
        return $this->belongsTo(StockSource::class, 'stock_source_id');
    }

    // (опційно на майбутнє)
    // public function scopeActive($q) { return $q->where('is_active', true); }
}
