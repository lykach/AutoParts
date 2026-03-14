<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
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

    protected $appends = [
        'resolved_phone',
        'resolved_address_uk',
        'resolved_work_schedule_uk',
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

    public function getResolvedPhoneAttribute(): ?string
    {
        if (! $this->shouldInherit('phone')) {
            return filled($this->phone) ? trim((string) $this->phone) : null;
        }

        return static::extractPrimaryPhoneFromStore($this->store);
    }

    public function getResolvedAddressUkAttribute(): ?string
    {
        if (! $this->shouldInherit('address_uk')) {
            return filled($this->address_uk) ? trim((string) $this->address_uk) : null;
        }

        return static::buildAddressUkFromStore($this->store);
    }

    public function getResolvedWorkScheduleUkAttribute(): ?string
    {
        if (! $this->shouldInherit('work_schedule_uk')) {
            return filled($this->work_schedule_uk) ? trim((string) $this->work_schedule_uk) : null;
        }

        return static::buildWorkScheduleUkFromStore($this->store);
    }

    public function shouldInherit(string $field): bool
    {
        return (bool) data_get($this->settings ?? [], "inherit.$field", true);
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

        $dayLabels = [
            'mon' => 'Пн',
            'tue' => 'Вт',
            'wed' => 'Ср',
            'thu' => 'Чт',
            'fri' => 'Пт',
            'sat' => 'Сб',
            'sun' => 'Нд',
        ];

        $regularLines = [];

        if (is_array($days)) {
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

                    $regularLines[] = $line;
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

                $line = empty($intervalStrings)
                    ? "{$label}: за графіком"
                    : "{$label}: " . implode(', ', $intervalStrings);

                if ($note) {
                    $line .= " ({$note})";
                }

                $regularLines[] = $line;
            }
        }

        $exceptionLines = [];
        $exceptions = is_array($store->working_exceptions) ? $store->working_exceptions : [];

        foreach ($exceptions as $exception) {
            $dateRaw = $exception['date'] ?? null;
            if (! filled($dateRaw)) {
                continue;
            }

            try {
                $date = Carbon::parse($dateRaw)->format('d.m.Y');
            } catch (\Throwable) {
                $date = (string) $dateRaw;
            }

            $title = filled($exception['title'] ?? null) ? trim((string) $exception['title']) : null;
            $note = filled($exception['note'] ?? null) ? trim((string) $exception['note']) : null;
            $isClosed = (bool) ($exception['is_closed'] ?? false);

            if ($isClosed) {
                $line = "{$date} — вихідний";
            } else {
                $intervals = is_array($exception['intervals'] ?? null) ? $exception['intervals'] : [];
                $intervalStrings = [];

                foreach ($intervals as $interval) {
                    $from = trim((string) ($interval['from'] ?? ''));
                    $to = trim((string) ($interval['to'] ?? ''));

                    if ($from !== '' && $to !== '') {
                        $intervalStrings[] = "{$from}-{$to}";
                    }
                }

                $line = "{$date} — " . (empty($intervalStrings) ? 'спецграфік' : implode(', ', $intervalStrings));
            }

            if ($title) {
                $line .= " ({$title})";
            }

            if ($note) {
                $line .= " — {$note}";
            }

            $exceptionLines[] = $line;
        }

        $result = [];

        if (! empty($regularLines)) {
            $result[] = implode(PHP_EOL, $regularLines);
        }

        if (! empty($exceptionLines)) {
            $result[] = 'Святкові дні / винятки:' . PHP_EOL . implode(PHP_EOL, $exceptionLines);
        }

        return empty($result) ? null : implode(PHP_EOL . PHP_EOL, $result);
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

            $settings = is_array($row->settings) ? $row->settings : [];
            $settings['inherit'] = array_merge([
                'phone' => true,
                'address_uk' => true,
                'work_schedule_uk' => true,
            ], is_array($settings['inherit'] ?? null) ? $settings['inherit'] : []);

            $row->settings = $settings;

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