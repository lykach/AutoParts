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

        // ✅ address text fields
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

        // content
        'title_uk', 'title_en', 'title_ru',
        'description_uk', 'description_en', 'description_ru',

        // footer/brand title
        'footer_title_uk', 'footer_title_en', 'footer_title_ru',

        // H1
        'h1_uk', 'h1_en', 'h1_ru',

        // meta
        'meta_title_uk', 'meta_title_en', 'meta_title_ru',
        'meta_description_uk', 'meta_description_en', 'meta_description_ru',
        'meta_keywords_uk', 'meta_keywords_en', 'meta_keywords_ru',

        'canonical_url',
        'robots',
        'seo',

        // OpenGraph
        'og_title_uk', 'og_title_en', 'og_title_ru',
        'og_description_uk', 'og_description_en', 'og_description_ru',
        'og_image',

        // legal
        'company_name',
        'edrpou',
        'vat',
        'legal_address',

        // localization
        'timezone',
        'currency',
        'default_language',

        // delivery/pickup
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

    // ✅ relation для country_id (залишається)
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
            ->orderBy('priority')
            ->orderBy('id');
    }

    public function stockSources(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\StockSource::class, 'store_stock_sources', 'store_id', 'stock_source_id')
            ->withPivot([
                'is_active',
                'priority',
                'markup_percent',
                'min_delivery_days',
                'max_delivery_days',
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
        if ($this->is_main) return $this->stockSourceLinks;

        if (! $this->inherit_defaults) return $this->stockSourceLinks;

        $override = (bool) data_get($this->settings ?? [], 'overrides.stock_sources', false);
        if ($override) return $this->stockSourceLinks;

        if ($this->stockSourceLinks()->exists()) return $this->stockSourceLinks;

        return $this->parent ? $this->parent->resolvedStockSourceLinks() : $this->stockSourceLinks;
    }

    protected function resolveValue(string $key, int $depth = 0)
    {
        if ($depth > 10) return $this->{$key} ?? null;

        if ($this->is_main) return $this->{$key} ?? null;

        if (! $this->inherit_defaults) return $this->{$key} ?? null;

        $overrides = (array) data_get($this->settings ?? [], 'overrides', []);

        $group = match ($key) {
            'working_hours', 'working_exceptions' => 'working_hours',

            'payment_methods', 'delivery_methods', 'services',
            'pickup_instructions_uk','pickup_instructions_en','pickup_instructions_ru',
            'delivery_info_uk','delivery_info_en','delivery_info_ru' => 'delivery',

            'phones','additional_emails','messengers','social_links','email','website_url' => 'contacts',

            // ✅ SEO group: full
            'title_uk','title_en','title_ru',
            'description_uk','description_en','description_ru',

            'footer_title_uk','footer_title_en','footer_title_ru',
            'h1_uk','h1_en','h1_ru',

            'meta_title_uk','meta_title_en','meta_title_ru',
            'meta_description_uk','meta_description_en','meta_description_ru',
            'meta_keywords_uk','meta_keywords_en','meta_keywords_ru',

            'og_title_uk','og_title_en','og_title_ru',
            'og_description_uk','og_description_en','og_description_ru',
            'og_image',

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
        if ($key === 'default_language') {
            return \App\Models\Language::query()->where('is_default', true)->value('code') ?: 'uk';
        }

        return null;
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
                if ($store->type === 'main') $store->type = 'branch';
            }

            $base = $store->slug ?: Str::slug($store->name_uk ?: 'store');
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