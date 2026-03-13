<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DeliveryPickupPoint extends Model
{
    use SoftDeletes;

    protected $table = 'delivery_pickup_points';

    protected $fillable = [
        'store_id',
        'code',
        'name_uk',
        'name_en',
        'name_ru',
        'address_uk',
        'address_en',
        'address_ru',
        'phone',
        'work_schedule_uk',
        'work_schedule_en',
        'work_schedule_ru',
        'is_active',
        'sort_order',
        'settings',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'settings' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function stockSourceLinks(): HasMany
    {
        return $this->hasMany(PickupPointStoreStockSource::class, 'pickup_point_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::saving(function (self $row) {
            $row->name_uk = trim((string) $row->name_uk);

            $row->name_en = filled($row->name_en) ? trim((string) $row->name_en) : null;
            $row->name_ru = filled($row->name_ru) ? trim((string) $row->name_ru) : null;

            $row->address_uk = filled($row->address_uk) ? trim((string) $row->address_uk) : null;
            $row->address_en = filled($row->address_en) ? trim((string) $row->address_en) : null;
            $row->address_ru = filled($row->address_ru) ? trim((string) $row->address_ru) : null;

            $row->phone = filled($row->phone) ? trim((string) $row->phone) : null;

            $row->work_schedule_uk = filled($row->work_schedule_uk) ? trim((string) $row->work_schedule_uk) : null;
            $row->work_schedule_en = filled($row->work_schedule_en) ? trim((string) $row->work_schedule_en) : null;
            $row->work_schedule_ru = filled($row->work_schedule_ru) ? trim((string) $row->work_schedule_ru) : null;

            $row->sort_order = filled($row->sort_order) ? (int) $row->sort_order : 100;
            $row->settings = is_array($row->settings) ? $row->settings : [];

            if (! filled($row->code)) {
                $storeId = (int) $row->store_id;
                $base = Str::slug($row->name_uk ?: 'pickup-point');
                $row->code = "pickup-{$storeId}-{$base}";
            } else {
                $row->code = Str::slug((string) $row->code);
            }
        });
    }
}