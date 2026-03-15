<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Store extends Model
{
    protected $fillable = [
        'parent_id',
        'inherit_defaults',
        'country_id',
        'currency_id',

        'is_main',
        'code',
        'slug',
        'type',

        'name_uk',

        'is_active',
        'sort_order',

        'email',
        'website_url',
        'phones',
        'additional_emails',
        'messengers',
        'social_links',

        'country_name',
        'region',
        'city',
        'address_line1',
        'address_line2',
        'postal_code',
        'address_note',

        'lat',
        'lng',
        'google_maps_url',
        'google_place_id',

        'working_hours',
        'working_exceptions',

        'logo',
        'cover_image',

        'title_uk', 'title_en', 'title_ru',
        'description_uk', 'description_en', 'description_ru',

        'footer_title_uk', 'footer_title_en', 'footer_title_ru',

        'h1_uk', 'h1_en', 'h1_ru',

        'meta_title_uk', 'meta_title_en', 'meta_title_ru',
        'meta_description_uk', 'meta_description_en', 'meta_description_ru',
        'meta_keywords_uk', 'meta_keywords_en', 'meta_keywords_ru',

        'canonical_url',
        'robots',
        'seo',

        'og_title_uk', 'og_title_en', 'og_title_ru',
        'og_description_uk', 'og_description_en', 'og_description_ru',
        'og_image',

        'company_name',
        'edrpou',
        'vat',
        'legal_address',

        'timezone',
        'currency',
        'default_language',

        'pickup_instructions_uk', 'pickup_instructions_en', 'pickup_instructions_ru',
        'delivery_info_uk', 'delivery_info_en', 'delivery_info_ru',
        'payment_methods',
        'delivery_methods',
        'services',

        'b2b_contacts',

        'settings',
        'internal_note',
    ];

    protected $casts = [
        'inherit_defaults' => 'boolean',
        'is_main' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',

        'phones' => 'array',
        'additional_emails' => 'array',
        'messengers' => 'array',
        'social_links' => 'array',

        'working_hours' => 'array',
        'working_exceptions' => 'array',

        'seo' => 'array',

        'payment_methods' => 'array',
        'delivery_methods' => 'array',
        'services' => 'array',
        'b2b_contacts' => 'array',
        'settings' => 'array',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Store::class, 'parent_id')->orderBy('sort_order');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Country::class, 'country_id');
    }

    public function currencyModel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    public function stockSourceLinks(): HasMany
    {
        return $this->hasMany(\App\Models\StoreStockSource::class, 'store_id')
            ->with(['stockSource', 'location'])
            ->orderBy('priority')
            ->orderBy('id');
    }

    public function stockSources(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\StockSource::class, 'store_stock_sources', 'store_id', 'stock_source_id')
            ->withPivot([
                'stock_source_location_id',
                'is_active',
                'priority',
                'delivery_unit',
                'delivery_min',
                'delivery_max',
                'settings',
                'note',
            ])
            ->withTimestamps();
    }

    public function getActiveStockSourcesAttribute(): EloquentCollection
    {
        return $this->stockSources()
            ->wherePivot('is_active', true)
            ->orderByPivot('priority')
            ->get();
    }

    public function scopeMain(Builder $query): Builder
    {
        return $query->where('is_main', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name_uk ?: ('Store #' . $this->id);
    }

    public function resolvedCountryId(): ?int
    {
        return $this->resolveValue('country_id');
    }

    public function resolvedCurrencyId(): ?int
    {
        return $this->resolveValue('currency_id');
    }

    public function resolvedTimezone(): string
    {
        return $this->resolveValue('timezone') ?: 'Europe/Kyiv';
    }

    public function resolvedDefaultLanguage(): string
    {
        return $this->resolveValue('default_language') ?: 'uk';
    }

    public function resolvedStockSourceLinks(): Collection|EloquentCollection
    {
        if ($this->is_main) {
            return $this->stockSourceLinks;
        }

        if (! $this->inherit_defaults) {
            return $this->stockSourceLinks;
        }

        $override = (bool) data_get($this->settings ?? [], 'overrides.stock_sources', false);

        if ($override) {
            return $this->stockSourceLinks;
        }

        if ($this->stockSourceLinks()->exists()) {
            return $this->stockSourceLinks;
        }

        return $this->parent ? $this->parent->resolvedStockSourceLinks() : $this->stockSourceLinks;
    }

    public function resolvedWorkingHours(): array
    {
        $value = $this->resolveValue('working_hours');

        return is_array($value) ? $value : [];
    }

    public function resolvedWorkingHoursDays(): array
    {
        $days = data_get($this->resolvedWorkingHours(), 'days', []);

        return is_array($days) ? $days : [];
    }

    public function resolvedWorkingExceptions(): array
    {
        $value = $this->resolveValue('working_exceptions');

        return is_array($value) ? $value : [];
    }

    public function resolvedWorkingExceptionsForYear(?int $year = null): array
    {
        $year ??= (int) now($this->resolvedTimezone())->year;

        $items = $this->resolvedWorkingExceptions();
        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $date = $item['date'] ?? null;

            if (! filled($date)) {
                continue;
            }

            try {
                $baseDate = Carbon::parse($date);
            } catch (\Throwable $e) {
                continue;
            }

            $repeatAnnually = (bool) ($item['repeat_annually'] ?? false);

            if ($repeatAnnually) {
                $month = (int) $baseDate->month;
                $day = (int) $baseDate->day;

                if ($month === 2 && $day === 29 && ! checkdate(2, 29, $year)) {
                    $effectiveDate = Carbon::create($year, 2, 28)->toDateString();
                } else {
                    $effectiveDate = Carbon::create($year, $month, $day)->toDateString();
                }
            } else {
                $effectiveDate = $baseDate->toDateString();
            }

            $item['repeat_annually'] = $repeatAnnually;
            $item['effective_date'] = $effectiveDate;
            $item['month_day'] = $baseDate->format('m-d');
            $normalized[] = $item;
        }

        usort($normalized, fn (array $a, array $b) => strcmp(
            (string) ($a['effective_date'] ?? ''),
            (string) ($b['effective_date'] ?? '')
        ));

        return $normalized;
    }

    public function findWorkingExceptionForDate(Carbon $at): ?array
    {
        $at = $this->toStoreTimezone($at);
        $date = $at->toDateString();

        foreach ($this->resolvedWorkingExceptionsForYear((int) $at->year) as $item) {
            if (($item['effective_date'] ?? null) === $date) {
                return $item;
            }
        }

        return null;
    }

    public function getScheduleForDate(Carbon $at): array
    {
        $at = $this->toStoreTimezone($at);

        $exception = $this->findWorkingExceptionForDate($at);

        if ($exception) {
            $intervals = $this->normalizeIntervals($exception['intervals'] ?? []);

            return [
                'source' => 'exception',
                'date' => $at->toDateString(),
                'day_code' => $this->getDayCode($at),
                'is_closed' => (bool) ($exception['is_closed'] ?? false),
                'intervals' => $intervals,
                'title' => $exception['title'] ?? null,
                'note' => $exception['note'] ?? null,
                'type' => $exception['type'] ?? 'special',
                'repeat_annually' => (bool) ($exception['repeat_annually'] ?? false),
                'effective_date' => $exception['effective_date'] ?? $at->toDateString(),
            ];
        }

        $dayCode = $this->getDayCode($at);

        $dayConfig = collect($this->resolvedWorkingHoursDays())
            ->first(fn ($item) => is_array($item) && ($item['day'] ?? null) === $dayCode);

        if (! is_array($dayConfig)) {
            return [
                'source' => 'regular',
                'date' => $at->toDateString(),
                'day_code' => $dayCode,
                'is_closed' => true,
                'intervals' => [],
                'title' => null,
                'note' => null,
                'type' => 'regular',
                'repeat_annually' => false,
                'effective_date' => $at->toDateString(),
            ];
        }

        $intervals = $this->normalizeIntervals($dayConfig['intervals'] ?? []);
        $isClosed = (bool) ($dayConfig['is_closed'] ?? false) || empty($intervals);

        return [
            'source' => 'regular',
            'date' => $at->toDateString(),
            'day_code' => $dayCode,
            'is_closed' => $isClosed,
            'intervals' => $isClosed ? [] : $intervals,
            'title' => null,
            'note' => $dayConfig['note'] ?? null,
            'type' => 'regular',
            'repeat_annually' => false,
            'effective_date' => $at->toDateString(),
        ];
    }

    public function isOpenAt(Carbon $at): bool
    {
        $schedule = $this->getScheduleForDate($at);

        if ((bool) ($schedule['is_closed'] ?? true)) {
            return false;
        }

        $at = $this->toStoreTimezone($at);
        $currentMinutes = $this->minutesFromCarbon($at);

        foreach (($schedule['intervals'] ?? []) as $interval) {
            $from = $this->timeToMinutes($interval['from'] ?? null);
            $to = $this->timeToMinutes($interval['to'] ?? null);

            if ($from === null || $to === null) {
                continue;
            }

            if ($currentMinutes >= $from && $currentMinutes < $to) {
                return true;
            }
        }

        return false;
    }

    public function isOpenNow(): bool
    {
        return $this->isOpenAt(now($this->resolvedTimezone()));
    }

    public function getTodaySchedule(?Carbon $now = null): array
    {
        $now ??= now($this->resolvedTimezone());

        return $this->getScheduleForDate($now);
    }

    public function getNextOpeningAt(Carbon $at, int $daysAhead = 30): ?Carbon
    {
        $at = $this->toStoreTimezone($at);
        $daysAhead = max(1, min($daysAhead, 365));

        if (! $this->is_active) {
            return null;
        }

        $todaySchedule = $this->getScheduleForDate($at);

        if (! (bool) ($todaySchedule['is_closed'] ?? true)) {
            $nextToday = $this->findNextInterval($todaySchedule['intervals'] ?? [], $at);

            if ($nextToday && ! empty($nextToday['from'])) {
                return $this->makeDateTimeFromDateAndTime($at->toDateString(), $nextToday['from']);
            }
        }

        for ($offset = 1; $offset <= $daysAhead; $offset++) {
            $date = $at->copy()->startOfDay()->addDays($offset);
            $schedule = $this->getScheduleForDate($date);

            if ((bool) ($schedule['is_closed'] ?? true)) {
                continue;
            }

            $intervals = $schedule['intervals'] ?? [];
            $first = $intervals[0] ?? null;

            if (! is_array($first) || empty($first['from'])) {
                continue;
            }

            return $this->makeDateTimeFromDateAndTime($date->toDateString(), $first['from']);
        }

        return null;
    }

    public function getNextOpeningStatus(Carbon $at, int $daysAhead = 30): array
    {
        $at = $this->toStoreTimezone($at);
        $next = $this->getNextOpeningAt($at, $daysAhead);

        if (! $next) {
            return [
                'has_next_opening' => false,
                'next_opening_at' => null,
                'timezone' => $this->resolvedTimezone(),
                'label' => 'Найближче відкриття не знайдено',
                'relative_label' => null,
            ];
        }

        $sameDay = $next->isSameDay($at);
        $tomorrow = $next->isSameDay($at->copy()->addDay());

        $relativeLabel = match (true) {
            $sameDay => 'сьогодні о ' . $next->format('H:i'),
            $tomorrow => 'завтра о ' . $next->format('H:i'),
            default => $next->format('d.m.Y') . ' о ' . $next->format('H:i'),
        };

        return [
            'has_next_opening' => true,
            'next_opening_at' => $next->toDateTimeString(),
            'timezone' => $this->resolvedTimezone(),
            'label' => 'Відкриється ' . $relativeLabel,
            'relative_label' => $relativeLabel,
        ];
    }

    public function getOpenStatusAt(Carbon $at): array
    {
        $at = $this->toStoreTimezone($at);
        $schedule = $this->getScheduleForDate($at);
        $isOpen = $this->isOpenAt($at);
        $currentInterval = $isOpen ? $this->findCurrentInterval($schedule['intervals'] ?? [], $at) : null;
        $nextInterval = $isOpen ? null : $this->findNextInterval($schedule['intervals'] ?? [], $at);
        $nextOpening = $isOpen ? null : $this->getNextOpeningAt($at);

        return [
            'is_open' => $isOpen,
            'checked_at' => $at->toDateTimeString(),
            'timezone' => $this->resolvedTimezone(),
            'schedule' => $schedule,
            'current_interval' => $currentInterval,
            'next_interval' => $nextInterval,
            'next_opening_at' => $nextOpening?->toDateTimeString(),
            'reason' => $this->makeClosedReason($schedule, $at, $isOpen, $nextOpening),
            'label' => $this->makeStatusLabel($schedule, $at, $isOpen, $currentInterval, $nextOpening),
        ];
    }

    protected function findCurrentInterval(array $intervals, Carbon $at): ?array
    {
        $currentMinutes = $this->minutesFromCarbon($at);

        foreach ($intervals as $interval) {
            $from = $this->timeToMinutes($interval['from'] ?? null);
            $to = $this->timeToMinutes($interval['to'] ?? null);

            if ($from === null || $to === null) {
                continue;
            }

            if ($currentMinutes >= $from && $currentMinutes < $to) {
                return $interval;
            }
        }

        return null;
    }

    protected function findNextInterval(array $intervals, Carbon $at): ?array
    {
        $currentMinutes = $this->minutesFromCarbon($at);

        foreach ($intervals as $interval) {
            $from = $this->timeToMinutes($interval['from'] ?? null);

            if ($from === null) {
                continue;
            }

            if ($from > $currentMinutes) {
                return $interval;
            }
        }

        return null;
    }

    protected function makeClosedReason(array $schedule, Carbon $at, bool $isOpen, ?Carbon $nextOpening = null): ?string
    {
        if ($isOpen) {
            return null;
        }

        if (! $this->is_active) {
            return 'Магазин вимкнений';
        }

        if ((bool) ($schedule['is_closed'] ?? false)) {
            if (($schedule['source'] ?? null) === 'exception') {
                $title = trim((string) ($schedule['title'] ?? ''));

                if ($title !== '') {
                    return 'Зачинено: ' . $title;
                }

                return 'Зачинено за винятком графіка';
            }

            if ($nextOpening) {
                return 'Зачинено, відкриється ' . $this->formatRelativeDateTime($nextOpening, $at);
            }

            return 'Зачинено за графіком';
        }

        $nextInterval = $this->findNextInterval($schedule['intervals'] ?? [], $at);

        if ($nextInterval && ! empty($nextInterval['from'])) {
            return 'Зачинено зараз, відкриється о ' . $nextInterval['from'];
        }

        if ($nextOpening) {
            return 'На сьогодні вже зачинено, відкриється ' . $this->formatRelativeDateTime($nextOpening, $at);
        }

        return 'На сьогодні вже зачинено';
    }

    protected function makeStatusLabel(array $schedule, Carbon $at, bool $isOpen, ?array $currentInterval = null, ?Carbon $nextOpening = null): string
    {
        if (! $this->is_active) {
            return 'Магазин вимкнений';
        }

        if ($isOpen) {
            if ($currentInterval && ! empty($currentInterval['to'])) {
                return 'Відкрито до ' . $currentInterval['to'];
            }

            return 'Відкрито';
        }

        if ($nextOpening) {
            return 'Зачинено • відкриється ' . $this->formatRelativeDateTime($nextOpening, $at);
        }

        if (($schedule['source'] ?? null) === 'exception' && ! empty($schedule['title'])) {
            return 'Зачинено • ' . $schedule['title'];
        }

        return 'Зачинено';
    }

    protected function normalizeIntervals(mixed $intervals): array
    {
        if (! is_array($intervals)) {
            return [];
        }

        $normalized = [];

        foreach ($intervals as $interval) {
            if (! is_array($interval)) {
                continue;
            }

            $from = trim((string) ($interval['from'] ?? ''));
            $to = trim((string) ($interval['to'] ?? ''));

            if (! $this->isValidTime($from) || ! $this->isValidTime($to)) {
                continue;
            }

            $fromMinutes = $this->timeToMinutes($from);
            $toMinutes = $this->timeToMinutes($to);

            if ($fromMinutes === null || $toMinutes === null) {
                continue;
            }

            if ($toMinutes <= $fromMinutes) {
                continue;
            }

            $normalized[] = [
                'from' => $from,
                'to' => $to,
                '_from_minutes' => $fromMinutes,
                '_to_minutes' => $toMinutes,
            ];
        }

        usort($normalized, fn (array $a, array $b) => $a['_from_minutes'] <=> $b['_from_minutes']);

        $merged = [];

        foreach ($normalized as $interval) {
            if (empty($merged)) {
                $merged[] = $interval;
                continue;
            }

            $lastIndex = array_key_last($merged);
            $last = $merged[$lastIndex];

            if ($interval['_from_minutes'] <= $last['_to_minutes']) {
                $merged[$lastIndex]['_to_minutes'] = max($last['_to_minutes'], $interval['_to_minutes']);
                $merged[$lastIndex]['to'] = sprintf(
                    '%02d:%02d',
                    intdiv($merged[$lastIndex]['_to_minutes'], 60),
                    $merged[$lastIndex]['_to_minutes'] % 60
                );
            } else {
                $merged[] = $interval;
            }
        }

        return array_map(fn (array $item) => [
            'from' => $item['from'],
            'to' => $item['to'],
        ], $merged);
    }

    protected function isValidTime(?string $value): bool
    {
        if (! is_string($value) || ! preg_match('/^\d{2}:\d{2}$/', $value)) {
            return false;
        }

        [$hour, $minute] = array_map('intval', explode(':', $value));

        return $hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59;
    }

    protected function timeToMinutes(?string $value): ?int
    {
        if (! $this->isValidTime($value)) {
            return null;
        }

        [$hour, $minute] = array_map('intval', explode(':', $value));

        return ($hour * 60) + $minute;
    }

    protected function getDayCode(Carbon $at): string
    {
        return match ((int) $at->dayOfWeekIso) {
            1 => 'mon',
            2 => 'tue',
            3 => 'wed',
            4 => 'thu',
            5 => 'fri',
            6 => 'sat',
            7 => 'sun',
        };
    }

    protected function minutesFromCarbon(Carbon $at): int
    {
        return ((int) $at->format('H') * 60) + (int) $at->format('i');
    }

    protected function makeDateTimeFromDateAndTime(string $date, string $time): ?Carbon
    {
        if (! $this->isValidTime($time)) {
            return null;
        }

        try {
            return Carbon::createFromFormat(
                'Y-m-d H:i',
                $date . ' ' . $time,
                $this->resolvedTimezone()
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function toStoreTimezone(Carbon $at): Carbon
    {
        return $at->copy()->timezone($this->resolvedTimezone());
    }

    protected function formatRelativeDateTime(Carbon $target, Carbon $base): string
    {
        $target = $this->toStoreTimezone($target);
        $base = $this->toStoreTimezone($base);

        if ($target->isSameDay($base)) {
            return 'сьогодні о ' . $target->format('H:i');
        }

        if ($target->isSameDay($base->copy()->addDay())) {
            return 'завтра о ' . $target->format('H:i');
        }

        return $target->format('d.m.Y') . ' о ' . $target->format('H:i');
    }

    protected function resolveValue(string $key, int $depth = 0)
    {
        if ($depth > 10) {
            return $this->{$key} ?? null;
        }

        if ($this->is_main) {
            return $this->{$key} ?? null;
        }

        if (! $this->inherit_defaults) {
            return $this->{$key} ?? null;
        }

        $overrides = (array) data_get($this->settings ?? [], 'overrides', []);

        $group = match ($key) {
            'working_hours', 'working_exceptions' => 'working_hours',

            'payment_methods', 'delivery_methods', 'services',
            'pickup_instructions_uk', 'pickup_instructions_en', 'pickup_instructions_ru',
            'delivery_info_uk', 'delivery_info_en', 'delivery_info_ru' => 'delivery',

            'phones', 'additional_emails', 'messengers', 'social_links', 'email', 'website_url' => 'contacts',

            'title_uk', 'title_en', 'title_ru',
            'description_uk', 'description_en', 'description_ru',

            'footer_title_uk', 'footer_title_en', 'footer_title_ru',
            'h1_uk', 'h1_en', 'h1_ru',

            'meta_title_uk', 'meta_title_en', 'meta_title_ru',
            'meta_description_uk', 'meta_description_en', 'meta_description_ru',
            'meta_keywords_uk', 'meta_keywords_en', 'meta_keywords_ru',

            'og_title_uk', 'og_title_en', 'og_title_ru',
            'og_description_uk', 'og_description_en', 'og_description_ru',
            'og_image',

            'canonical_url', 'robots', 'seo' => 'seo',

            'company_name', 'edrpou', 'vat', 'legal_address' => 'legal',

            default => null,
        };

        if ($group && (($overrides[$group] ?? false) === true)) {
            return $this->{$key} ?? null;
        }

        $own = $this->{$key} ?? null;
        $isEmptyArray = is_array($own) && count($own) === 0;

        if ($own !== null && ! $isEmptyArray && $own !== '') {
            return $own;
        }

        if ($this->parent) {
            return $this->parent->resolveValue($key, $depth + 1);
        }

        if ($key === 'currency_id') {
            return \App\Models\Currency::query()->where('is_default', true)->value('id');
        }

        if ($key === 'country_id') {
            return \App\Models\Country::query()->where('code', 'UA')->value('id');
        }

        if ($key === 'default_language') {
            return \App\Models\Language::query()->where('is_default', true)->value('code') ?: 'uk';
        }

        return null;
    }

    protected static function normalizeWorkingExceptions(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $date = null;
            if (isset($item['date']) && filled($item['date'])) {
                try {
                    $date = Carbon::parse($item['date'])->toDateString();
                } catch (\Throwable $e) {
                    $date = null;
                }
            }

            $isClosed = (bool) ($item['is_closed'] ?? false);
            $repeatAnnually = (bool) ($item['repeat_annually'] ?? false);

            $intervals = [];
            if (! $isClosed && is_array($item['intervals'] ?? null)) {
                foreach ($item['intervals'] as $interval) {
                    if (! is_array($interval)) {
                        continue;
                    }

                    $from = trim((string) ($interval['from'] ?? ''));
                    $to = trim((string) ($interval['to'] ?? ''));

                    if ($from === '' || $to === '') {
                        continue;
                    }

                    $intervals[] = [
                        'from' => $from,
                        'to' => $to,
                    ];
                }
            }

            $normalized[] = [
                'type' => $item['type'] ?? 'holiday',
                'date' => $date,
                'repeat_annually' => $repeatAnnually,
                'is_closed' => $isClosed,
                'title' => filled($item['title'] ?? null) ? trim((string) $item['title']) : null,
                'note' => filled($item['note'] ?? null) ? trim((string) $item['note']) : null,
                'intervals' => $isClosed ? [] : $intervals,
            ];
        }

        usort($normalized, fn (array $a, array $b) => strcmp(
            (string) ($a['date'] ?? ''),
            (string) ($b['date'] ?? '')
        ));

        return array_values($normalized);
    }

    protected static function booted(): void
    {
        static::saving(function (Store $store) {
            if ($store->sort_order === null) {
                $max = (int) (static::query()->max('sort_order') ?? 0);
                $store->sort_order = $max > 0 ? ($max + 10) : 100;
            }

            $store->type = in_array($store->type, ['main', 'branch'], true) ? $store->type : 'branch';

            if ($store->is_main) {
                $existsAnotherMain = static::query()
                    ->where('is_main', true)
                    ->when($store->id, fn ($q) => $q->where('id', '!=', $store->id))
                    ->exists();

                if ($existsAnotherMain) {
                    throw ValidationException::withMessages([
                        'is_main' => 'Головний магазин може бути лише один.',
                    ]);
                }

                $store->parent_id = null;
                $store->type = 'main';
                $store->inherit_defaults = false;

                $store->currency_id = \App\Models\Currency::query()->where('is_default', true)->value('id') ?? $store->currency_id;
                $store->default_language = \App\Models\Language::query()->where('is_default', true)->value('code') ?? ($store->default_language ?: 'uk');

                $store->timezone = $store->timezone ?: 'Europe/Kyiv';

                $settings = is_array($store->settings) ? $store->settings : [];
                $settings['overrides'] = [];
                $store->settings = $settings;
            } else {
                if ($store->type === 'main') {
                    $store->type = 'branch';
                }
            }

            $base = $store->slug ?: Str::slug($store->name_uk ?: 'store');
            $store->slug = static::makeUniqueSlug($base, $store->id);

            $store->website_url = $store->website_url ? trim($store->website_url) : null;
            $store->google_maps_url = $store->google_maps_url ? trim($store->google_maps_url) : null;
            $store->canonical_url = $store->canonical_url ? trim($store->canonical_url) : null;

            if (! empty($store->country_id) && (blank($store->country_name) || $store->isDirty('country_id'))) {
                $name = \App\Models\Country::query()
                    ->whereKey($store->country_id)
                    ->value('name_uk');

                if ($name) {
                    $store->country_name = $name;
                }
            }

            if (empty($store->country_id) && $store->isDirty('country_id')) {
                $store->country_name = null;
            }

            if ($store->lat !== null) {
                $lat = (float) $store->lat;
                if ($lat < -90 || $lat > 90) {
                    $store->lat = null;
                }
            }

            if ($store->lng !== null) {
                $lng = (float) $store->lng;
                if ($lng < -180 || $lng > 180) {
                    $store->lng = null;
                }
            }

            $store->working_exceptions = static::normalizeWorkingExceptions($store->working_exceptions);

            $settings = is_array($store->settings) ? $store->settings : [];
            if (! isset($settings['overrides']) || ! is_array($settings['overrides'])) {
                $settings['overrides'] = [];
            }
            if (! isset($settings['localization']) || ! is_array($settings['localization'])) {
                $settings['localization'] = [];
            }
            $store->settings = $settings;
        });
    }

    protected static function makeUniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base;
        $i = 2;

        while (
            static::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }
}