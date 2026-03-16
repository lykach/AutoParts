<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CityCourierZone extends Model
{
    use SoftDeletes;

    protected $table = 'city_courier_zones';

    protected $fillable = [
        'store_id',
        'code',
        'name_uk',
        'name_en',
        'name_ru',
        'city_uk',
        'city_en',
        'city_ru',
        'description_uk',
        'description_en',
        'description_ru',
        'delivery_price',
        'free_from_amount',
        'cash_allowed',
        'card_allowed',
        'cod_allowed',
        'min_order_amount',
        'max_order_amount',
        'weight_limit_kg',
        'eta_min_minutes',
        'eta_max_minutes',
        'manager_note',
        'is_active',
        'sort_order',
        'settings',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'delivery_price' => 'decimal:2',
        'free_from_amount' => 'decimal:2',
        'cash_allowed' => 'boolean',
        'card_allowed' => 'boolean',
        'cod_allowed' => 'boolean',
        'min_order_amount' => 'decimal:2',
        'max_order_amount' => 'decimal:2',
        'weight_limit_kg' => 'decimal:3',
        'eta_min_minutes' => 'integer',
        'eta_max_minutes' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'settings' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(CityCourierZoneSlot::class, 'city_courier_zone_id');
    }

    public function exceptions(): HasManyThrough
    {
        return $this->hasManyThrough(
            CityCourierSlotException::class,
            CityCourierZoneSlot::class,
            'city_courier_zone_id',
            'city_courier_zone_slot_id',
            'id',
            'id',
        );
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeForCityUk(Builder $q, string $city): Builder
    {
        return $q->where('city_uk', trim($city));
    }

    protected static function booted(): void
    {
        static::saving(function (self $row) {
            $row->name_uk = trim((string) $row->name_uk);
            $row->name_en = filled($row->name_en) ? trim((string) $row->name_en) : null;
            $row->name_ru = filled($row->name_ru) ? trim((string) $row->name_ru) : null;

            $row->city_uk = trim((string) $row->city_uk);
            $row->city_en = filled($row->city_en) ? trim((string) $row->city_en) : null;
            $row->city_ru = filled($row->city_ru) ? trim((string) $row->city_ru) : null;

            $row->description_uk = filled($row->description_uk) ? trim((string) $row->description_uk) : null;
            $row->description_en = filled($row->description_en) ? trim((string) $row->description_en) : null;
            $row->description_ru = filled($row->description_ru) ? trim((string) $row->description_ru) : null;

            $row->manager_note = filled($row->manager_note) ? trim((string) $row->manager_note) : null;

            $row->delivery_price = filled($row->delivery_price) ? (float) $row->delivery_price : 0;
            $row->free_from_amount = filled($row->free_from_amount) ? (float) $row->free_from_amount : null;

            $row->min_order_amount = filled($row->min_order_amount) ? (float) $row->min_order_amount : null;
            $row->max_order_amount = filled($row->max_order_amount) ? (float) $row->max_order_amount : null;
            $row->weight_limit_kg = filled($row->weight_limit_kg) ? (float) $row->weight_limit_kg : null;

            if (
                filled($row->min_order_amount) &&
                filled($row->max_order_amount) &&
                (float) $row->max_order_amount < (float) $row->min_order_amount
            ) {
                $row->max_order_amount = $row->min_order_amount;
            }

            $row->eta_min_minutes = filled($row->eta_min_minutes) ? max(0, (int) $row->eta_min_minutes) : 60;
            $row->eta_max_minutes = filled($row->eta_max_minutes) ? max(0, (int) $row->eta_max_minutes) : 180;

            if ($row->eta_max_minutes < $row->eta_min_minutes) {
                $row->eta_max_minutes = $row->eta_min_minutes;
            }

            $row->sort_order = filled($row->sort_order) ? (int) $row->sort_order : 100;
            $row->settings = is_array($row->settings) ? $row->settings : [];

            if (! filled($row->code)) {
                $base = Str::slug($row->name_uk ?: 'city-courier-zone');
                $row->code = 'ccz-' . (int) $row->store_id . '-' . $base;
            } else {
                $row->code = Str::slug((string) $row->code);
            }
        });
    }
}