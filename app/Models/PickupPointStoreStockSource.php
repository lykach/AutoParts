<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupPointStoreStockSource extends Model
{
    protected $table = 'pickup_point_store_stock_sources';

    protected $fillable = [
        'pickup_point_id',
        'store_stock_source_id',
        'is_active',
        'priority',
        'transfer_time_unit',
        'transfer_time_min',
        'transfer_time_max',
        'cutoff_at',
        'note',
        'settings',
    ];

    protected $casts = [
        'pickup_point_id' => 'integer',
        'store_stock_source_id' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'transfer_time_min' => 'integer',
        'transfer_time_max' => 'integer',
        'settings' => 'array',
    ];

    public function pickupPoint(): BelongsTo
    {
        return $this->belongsTo(DeliveryPickupPoint::class, 'pickup_point_id');
    }

    public function storeStockSource(): BelongsTo
    {
        return $this->belongsTo(StoreStockSource::class, 'store_stock_source_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::saving(function (self $row) {
            $allowedUnits = ['minute', 'hour', 'day'];

            $row->priority = filled($row->priority) ? (int) $row->priority : 100;
            $row->transfer_time_min = filled($row->transfer_time_min) ? max(0, (int) $row->transfer_time_min) : 0;
            $row->transfer_time_max = filled($row->transfer_time_max) ? max(0, (int) $row->transfer_time_max) : 0;

            if ($row->transfer_time_max < $row->transfer_time_min) {
                $row->transfer_time_max = $row->transfer_time_min;
            }

            $row->transfer_time_unit = in_array($row->transfer_time_unit, $allowedUnits, true)
                ? $row->transfer_time_unit
                : 'hour';

            $row->settings = is_array($row->settings) ? $row->settings : [];
            $row->note = filled($row->note) ? trim((string) $row->note) : null;
            $row->cutoff_at = filled($row->cutoff_at) ? trim((string) $row->cutoff_at) : null;
        });
    }
}