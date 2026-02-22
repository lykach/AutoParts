<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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

        'name_uk', 'name_en', 'name_ru',
        'short_name_uk', 'short_name_en', 'short_name_ru',

        'is_active',
        'sort_order',

        'email',
        'website_url',
        'phones',
        'additional_emails',
        'messengers',
        'social_links',

        'country',
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
        'meta_title_uk', 'meta_title_en', 'meta_title_ru',
        'meta_description_uk', 'meta_description_en', 'meta_description_ru',
        'canonical_url',
        'robots',
        'seo',

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

    // ------------------------------------------------------------
    // Relations / Scopes
    // ------------------------------------------------------------
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

    /**
     * ✅ Pivot links (із priority/is_active/налаштуваннями)
     */
    public function stockSourceLinks(): HasMany
    {
        return $this->hasMany(\App\Models\StoreStockSource::class, 'store_id')
            ->orderBy('priority')
            ->orderBy('id');
    }

    /**
     * ✅ Зручно для whereHas / sync / вибірок
     */
    public function stockSources(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\StockSource::class, 'store_stock_sources', 'store_id', 'stock_source_id')
            ->withPivot([
                'is_active',
                'priority',
                'lead_time_days',
                'cutoff_time',
                'pickup_available',
                'price_multiplier',
                'extra_fee',
                'min_order_amount',
                'coverage',
                'settings',
                'note',
            ])
            ->withTimestamps();
    }

    /**
     * ✅ Швидкий доступ: активні джерела магазину (не resolved, а саме цього магазину)
     */
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
        return $this->name_uk ?: $this->name_en ?: $this->name_ru ?: ('Store #' . $this->id);
    }

    // ------------------------------------------------------------
    // Resolved getters (з урахуванням спадкування)
    // ------------------------------------------------------------
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
        return $this->resolveValue('timezone') ?: 'Europe/Uzhgorod';
    }

    public function resolvedDefaultLanguage(): string
    {
        return $this->resolveValue('default_language') ?: 'uk';
    }

    public function resolvedPaymentMethods(): ?array
    {
        return $this->resolveValue('payment_methods');
    }

    public function resolvedDeliveryMethods(): ?array
    {
        return $this->resolveValue('delivery_methods');
    }

    public function resolvedServices(): ?array
    {
        return $this->resolveValue('services');
    }

    public function resolvedPickupInstructions(string $locale = 'uk'): ?string
    {
        return $this->resolveValue('pickup_instructions_' . $locale);
    }

    public function resolvedDeliveryInfo(string $locale = 'uk'): ?string
    {
        return $this->resolveValue('delivery_info_' . $locale);
    }

    /**
     * ✅ Resolved stock sources links:
     * - main: свої
     * - inherit_defaults=false: свої
     * - overrides.stock_sources=true: свої
     * - якщо своїх нема → беремо з parent рекурсивно
     */
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

        if ($this->stockSourceLinks()->count() > 0) {
            return $this->stockSourceLinks;
        }

        return $this->parent ? $this->parent->resolvedStockSourceLinks() : $this->stockSourceLinks;
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
            'pickup_instructions_uk','pickup_instructions_en','pickup_instructions_ru',
            'delivery_info_uk','delivery_info_en','delivery_info_ru' => 'delivery',

            'phones','additional_emails','messengers','social_links','email','website_url' => 'contacts',

            'title_uk','title_en','title_ru',
            'description_uk','description_en','description_ru',
            'meta_title_uk','meta_title_en','meta_title_ru',
            'meta_description_uk','meta_description_en','meta_description_ru',
            'canonical_url','robots','seo' => 'seo',

            'company_name','edrpou','vat','legal_address' => 'legal',

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

        return null;
    }

    // ------------------------------------------------------------
    // Working hours (як у тебе було)
    // ------------------------------------------------------------
    public function getWorkingHoursAttribute($value): array
    {
        $data = is_array($value) ? $value : (json_decode($value ?? '[]', true) ?: []);

        if (isset($data['days']) && is_array($data['days'])) {
            return $data;
        }

        $daysOrder = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $days = [];

        foreach ($daysOrder as $day) {
            $intervals = $data[$day] ?? [];
            $intervals = is_array($intervals) ? array_values($intervals) : [];
            $intervals = $this->sanitizeIntervals($intervals);

            $days[] = [
                'day' => $day,
                'is_closed' => empty($intervals),
                'intervals' => $intervals,
                'note' => null,
            ];
        }

        return array_merge($data, ['days' => $days]);
    }

    public function setWorkingHoursAttribute($value): void
    {
        $data = $value;
        if (! is_array($data)) {
            $data = json_decode($value ?? '[]', true) ?: [];
        }

        if (isset($data['days']) && is_array($data['days'])) {
            $normalized = [];

            foreach ($data['days'] as $row) {
                $day = $row['day'] ?? null;
                if (! $day) continue;

                $isClosed = (bool)($row['is_closed'] ?? false);
                $intervals = $isClosed ? [] : (is_array($row['intervals'] ?? null) ? $row['intervals'] : []);
                $intervals = $this->sanitizeIntervals($intervals);

                if (! empty($intervals)) {
                    $isClosed = false;
                }

                $normalized[$day] = $isClosed ? [] : $intervals;
            }

            $this->attributes['working_hours'] = json_encode($normalized, JSON_UNESCAPED_UNICODE);
            return;
        }

        $clean = [];
        foreach (['mon','tue','wed','thu','fri','sat','sun'] as $day) {
            $intervals = $data[$day] ?? [];
            $intervals = is_array($intervals) ? $intervals : [];
            $clean[$day] = $this->sanitizeIntervals($intervals);
        }

        $this->attributes['working_hours'] = json_encode($clean, JSON_UNESCAPED_UNICODE);
    }

    protected function sanitizeIntervals(array $intervals): array
    {
        $clean = [];

        foreach ($intervals as $i) {
            if (! is_array($i)) continue;

            $from = trim((string)($i['from'] ?? ''));
            $to   = trim((string)($i['to'] ?? ''));

            if (! $this->isValidTime($from) || ! $this->isValidTime($to)) continue;

            $fromMin = $this->timeToMinutes($from);
            $toMin   = $this->timeToMinutes($to);

            if ($fromMin >= $toMin) continue;

            $clean[] = ['from' => $from, 'to' => $to, '_from' => $fromMin, '_to' => $toMin];
        }

        usort($clean, fn ($a, $b) => $a['_from'] <=> $b['_from']);

        $merged = [];
        foreach ($clean as $row) {
            if (empty($merged)) {
                $merged[] = $row;
                continue;
            }

            $lastIndex = count($merged) - 1;
            $last = $merged[$lastIndex];

            if ($row['_from'] <= $last['_to']) {
                $merged[$lastIndex]['_to'] = max($last['_to'], $row['_to']);
                $merged[$lastIndex]['to'] = $this->minutesToTime($merged[$lastIndex]['_to']);
                continue;
            }

            $merged[] = $row;
        }

        return array_values(array_map(fn ($r) => ['from' => $r['from'], 'to' => $r['to']], $merged));
    }

    protected function isValidTime(string $time): bool
    {
        return (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time);
    }

    protected function timeToMinutes(string $time): int
    {
        [$h, $m] = explode(':', $time);
        return ((int) $h) * 60 + (int) $m;
    }

    protected function minutesToTime(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return str_pad((string) $h, 2, '0', STR_PAD_LEFT)
            . ':'
            . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
    }

    // ------------------------------------------------------------
    // Boot
    // ------------------------------------------------------------
    protected static function booted(): void
    {
        static::saving(function (Store $store) {
            if ($store->is_main) {
                $store->parent_id = null;
                $store->type = 'main';
                $store->inherit_defaults = false;
            }

            $base = $store->slug ?: Str::slug($store->name_uk ?: $store->name_en ?: $store->name_ru ?: 'store');
            $store->slug = static::makeUniqueSlug($base, $store->id);

            $store->website_url = $store->website_url ? trim($store->website_url) : null;
            $store->google_maps_url = $store->google_maps_url ? trim($store->google_maps_url) : null;
            $store->canonical_url = $store->canonical_url ? trim($store->canonical_url) : null;

            if ($store->lat !== null) {
                $lat = (float) $store->lat;
                if ($lat < -90 || $lat > 90) $store->lat = null;
            }
            if ($store->lng !== null) {
                $lng = (float) $store->lng;
                if ($lng < -180 || $lng > 180) $store->lng = null;
            }

            // phones normalize + only one primary
            if (is_array($store->phones)) {
                $phones = array_values(array_filter(array_map(function ($p) {
                    if (! is_array($p)) return null;

                    $number = trim((string)($p['number'] ?? ''));
                    if ($number === '') return null;

                    // ✅ safety: якщо десь не через PhoneInput — нормалізуємо
                    $number = \App\Rules\UkrainianPhone::normalize($number) ?? $number;

                    return [
                        'label' => trim((string)($p['label'] ?? '')),
                        'number' => $number,
                        'is_primary' => (bool)($p['is_primary'] ?? false),
                    ];
                }, $store->phones)));

                $foundPrimary = false;
                foreach ($phones as $idx => $p) {
                    if ($p['is_primary'] && ! $foundPrimary) {
                        $foundPrimary = true;
                        continue;
                    }
                    if ($p['is_primary'] && $foundPrimary) {
                        $phones[$idx]['is_primary'] = false;
                    }
                }
                if (! $foundPrimary && ! empty($phones)) {
                    $phones[0]['is_primary'] = true;
                }

                $store->phones = $phones;
            }

            if (is_array($store->additional_emails)) {
                $store->additional_emails = array_values(array_filter(array_map(function ($e) {
                    if (! is_array($e)) return null;

                    $email = trim((string)($e['email'] ?? ''));
                    if ($email === '') return null;

                    return [
                        'label' => trim((string)($e['label'] ?? '')),
                        'email' => $email,
                    ];
                }, $store->additional_emails)));
            }

            $settings = is_array($store->settings) ? $store->settings : [];
            if (! isset($settings['overrides']) || ! is_array($settings['overrides'])) {
                $settings['overrides'] = [];
            }
            $store->settings = $settings;
        });
    }

    protected static function makeUniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base;
        $i = 2;

        while (static::query()
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
