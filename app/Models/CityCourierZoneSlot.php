<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CityCourierZoneSlot extends Model
{
    protected $table = 'city_courier_zone_slots';

    protected $fillable = [
        'city_courier_zone_id',
        'name',
        'work_days',
        'delivery_time_from',
        'delivery_time_to',
        'same_day_enabled',
        'same_day_cutoff_at',
        'is_active',
        'sort_order',
        'manager_note',
        'settings',
    ];

    protected $casts = [
        'city_courier_zone_id' => 'integer',
        'work_days' => 'array',
        'same_day_enabled' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'settings' => 'array',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(CityCourierZone::class, 'city_courier_zone_id');
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(CityCourierSlotException::class, 'city_courier_zone_slot_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function isWorkingDay(string $dayCode): bool
    {
        $days = is_array($this->work_days) ? $this->work_days : [];

        return in_array($dayCode, $days, true);
    }

    public static function normalizeDays(mixed $days): array
    {
        $allowedDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

        $days = is_array($days) ? $days : [];

        return array_values(array_unique(array_filter(
            $days,
            fn ($v) => in_array($v, $allowedDays, true)
        )));
    }

    public static function intervalsOverlap(
        string $fromA,
        string $toA,
        string $fromB,
        string $toB
    ): bool {
        return $fromA < $toB && $toA > $fromB;
    }

    public static function daysOverlap(array $daysA, array $daysB): bool
    {
        return ! empty(array_intersect(
            static::normalizeDays($daysA),
            static::normalizeDays($daysB),
        ));
    }

    protected static function booted(): void
    {
        static::saving(function (self $row) {
            $row->name = filled($row->name) ? trim((string) $row->name) : null;
            $row->manager_note = filled($row->manager_note) ? trim((string) $row->manager_note) : null;
            $row->sort_order = filled($row->sort_order) ? (int) $row->sort_order : 100;
            $row->settings = is_array($row->settings) ? $row->settings : [];
            $row->work_days = static::normalizeDays($row->work_days);

            if (
                filled($row->delivery_time_from) &&
                filled($row->delivery_time_to) &&
                $row->delivery_time_to < $row->delivery_time_from
            ) {
                $tmp = $row->delivery_time_from;
                $row->delivery_time_from = $row->delivery_time_to;
                $row->delivery_time_to = $tmp;
            }
        });
    }
}