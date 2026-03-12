<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreDeliveryMethod extends Model
{
    protected $table = 'store_delivery_methods';

    protected $fillable = [
        'store_id',
        'delivery_method_id',
        'is_active',
        'sort_order',
        'settings',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'delivery_method_id' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'settings' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(DeliveryMethod::class, 'delivery_method_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::saving(function (self $row) {
            $row->sort_order = filled($row->sort_order) ? (int) $row->sort_order : 100;
            $row->settings = is_array($row->settings) ? $row->settings : [];
        });
    }
}