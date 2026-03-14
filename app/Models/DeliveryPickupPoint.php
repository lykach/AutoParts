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
        return $this->hasMany(PickupPointStoreStockSource::class, 'pickup_point_id')
            ->with([
                'storeStockSource.store',
                'storeStockSource.stockSource',
                'storeStockSource.location',
            ])
            ->orderBy('priority')
            ->orderBy('id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public static function buildPrefillFromStore(?Store $store): array
    {
        if (! $store) {
            return [];
        }

        return [
            'address_uk' => static::buildAddressUkFromStore($store),
            'phone' => static::extractPrimaryPhoneFromStore($store),
            'work_schedule_uk' => static::buildWorkScheduleUkFromStore($store),
        ];
    }

    public static function buildAddressUkFromStore(?Store $store): ?string
    {
        if (! $store) {
            return null;
        }

        $parts = array_filter([
            filled($store->country_name) ? trim((string) $store->country_name) : null,
            filled($store->region) ? trim((string) $store->region) : null,
            filled($store->city) ? trim((string) $store->city) : null,
            filled($store->address_line1) ? trim((string) $store->address_line1) : null,
            filled($store->address_line2) ? trim((string) $store->address_line2) : null,
            filled($store->postal_code) ? 'Індекс: ' . trim((string) $store->postal_code) : null,
        ], fn ($value) => filled($value));

        if (empty($parts)) {
            return null;
        }

        return implode(', ', $parts);
    }

    public static function extractPrimaryPhoneFromStore(?Store $store): ?string
    {
        if (! $store) {
            return null;
        }

        $phones = is_array($store->phones) ? $store->phones : [];
        if (empty($phones)) {
            return null;
        }

        $primary = collect($phones)->firstWhere('is_primary', true);
        $first = $phones[0] ?? null;

        $number = $primary['number'] ?? ($first['number'] ?? null);

        return filled($number) ? trim((string) $number) : null;
    }

    public static function buildWorkScheduleUkFromStore(?Store $store): ?string
    {
        if (! $store) {
            return null;
        }

        $workingHours = is_array($store->working_hours) ? $store->working_hours : [];
        $days = data_get($workingHours, 'days', []);

        if (! is_array($days) || empty($days)) {
            return null;
        }

        $dayLabels = [
            'mon' => 'Пн',
            'tue' => 'Вт',
            'wed' => 'Ср',
            'thu' => 'Чт',
            'fri' => 'Пт',
            'sat' => 'Сб',
            'sun' => 'Нд',
        ];

        $lines = [];

        foreach ($days as $day) {
            $code = $day['day'] ?? null;
            if (! $code || ! isset($dayLabels[$code])) {
                continue;
            }

            $label = $dayLabels[$code];
            $isClosed = (bool) ($day['is_closed'] ?? false);
            $note = filled($day['note'] ?? null) ? trim((string) $day['note']) : null;

            if ($isClosed) {
                $line = "{$label}: вихідний";
                if ($note) {
                    $line .= " ({$note})";
                }

                $lines[] = $line;
                continue;
            }

            $intervals = is_array($day['intervals'] ?? null) ? $day['intervals'] : [];
            $intervalStrings = [];

            foreach ($intervals as $interval) {
                $from = trim((string) ($interval['from'] ?? ''));
                $to = trim((string) ($interval['to'] ?? ''));

                if ($from !== '' && $to !== '') {
                    $intervalStrings[] = "{$from}-{$to}";
                }
            }

            if (empty($intervalStrings)) {
                $line = "{$label}: за графіком";
            } else {
                $line = "{$label}: " . implode(', ', $intervalStrings);
            }

            if ($note) {
                $line .= " ({$note})";
            }

            $lines[] = $line;
        }

        return empty($lines) ? null : implode(PHP_EOL, $lines);
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