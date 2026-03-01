<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class StoreStockSource extends Model
{
    protected $table = 'store_stock_sources';

    protected $fillable = [
        'store_id',
        'stock_source_id',
        'stock_source_location_id',   // ✅ ВАЖЛИВО

        'is_active',
        'priority',

        // лишаємо поки що (можливо використаємо пізніше/або прибереш міграцією)
        'delivery_unit',
        'delivery_min',
        'delivery_max',

        'settings',
        'note',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'stock_source_id' => 'integer',
        'stock_source_location_id' => 'integer', // ✅ ВАЖЛИВО

        'is_active' => 'boolean',
        'priority' => 'integer',

        'delivery_unit' => 'string',
        'delivery_min' => 'integer',
        'delivery_max' => 'integer',

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

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockSourceLocation::class, 'stock_source_location_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::saving(function (self $row) {
            // ✅ Автопідстановка stock_source_id по location (надійність)
            if (filled($row->stock_source_location_id)) {
                $srcId = StockSourceLocation::query()
                    ->whereKey($row->stock_source_location_id)
                    ->value('stock_source_id');

                if ($srcId) {
                    $row->stock_source_id = (int) $srcId;
                }
            }

            // Мінімальна нормалізація
            $row->priority = filled($row->priority) ? (int) $row->priority : 100;

            $row->settings = is_array($row->settings) ? $row->settings : [];
            $row->note = $row->note ? trim((string) $row->note) : null;
        });
    }
}