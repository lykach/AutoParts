<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CityCourierSlotException extends Model
{
    protected $table = 'city_courier_slot_exceptions';

    protected $fillable = [
        'city_courier_zone_slot_id',
        'exception_date',
        'is_closed',
        'override_delivery_time_from',
        'override_delivery_time_to',
        'override_cutoff_at',
        'override_price',
        'override_eta_min_minutes',
        'override_eta_max_minutes',
        'max_orders',
        'manager_note',
        'settings',
    ];

    protected $casts = [
        'city_courier_zone_slot_id' => 'integer',
        'exception_date' => 'date',
        'is_closed' => 'boolean',
        'override_price' => 'decimal:2',
        'override_eta_min_minutes' => 'integer',
        'override_eta_max_minutes' => 'integer',
        'max_orders' => 'integer',
        'settings' => 'array',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(CityCourierZoneSlot::class, 'city_courier_zone_slot_id');
    }

    public function scopeForDate(Builder $q, string $date): Builder
    {
        return $q->whereDate('exception_date', $date);
    }

    protected static function booted(): void
    {
        static::saving(function (self $row) {
            $row->manager_note = filled($row->manager_note) ? trim((string) $row->manager_note) : null;
            $row->settings = is_array($row->settings) ? $row->settings : [];

            $row->override_price = filled($row->override_price) ? (float) $row->override_price : null;
            $row->override_eta_min_minutes = filled($row->override_eta_min_minutes)
                ? max(0, (int) $row->override_eta_min_minutes)
                : null;
            $row->override_eta_max_minutes = filled($row->override_eta_max_minutes)
                ? max(0, (int) $row->override_eta_max_minutes)
                : null;
            $row->max_orders = filled($row->max_orders) ? max(0, (int) $row->max_orders) : null;

            if (
                filled($row->override_eta_min_minutes) &&
                filled($row->override_eta_max_minutes) &&
                (int) $row->override_eta_max_minutes < (int) $row->override_eta_min_minutes
            ) {
                $row->override_eta_max_minutes = $row->override_eta_min_minutes;
            }

            if (
                filled($row->override_delivery_time_from) &&
                filled($row->override_delivery_time_to) &&
                (string) $row->override_delivery_time_to < (string) $row->override_delivery_time_from
            ) {
                $tmp = $row->override_delivery_time_from;
                $row->override_delivery_time_from = $row->override_delivery_time_to;
                $row->override_delivery_time_to = $tmp;
            }
        });
    }
}