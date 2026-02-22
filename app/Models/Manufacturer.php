<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Manufacturer extends Model
{
    protected $fillable = [
        'name',
        'short_name',
        'slug',
        'logo',
        'country_id',
        'website_url',
        'catalog_url',
        'description_uk',
        'description_en',
        'description_ru',
        'is_oem',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'country_id' => 'integer',
        'is_oem' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // =========================
    // RELATIONS
    // =========================

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function synonyms(): HasMany
    {
        return $this->hasMany(ManufacturerSynonym::class, 'manufacturer_id');
    }

    // =========================
    // BOOT
    // =========================

    protected static function booted(): void
    {
        static::creating(function (self $manufacturer) {
            // short_name чистимо
            if ($manufacturer->short_name !== null) {
                $manufacturer->short_name = trim((string) $manufacturer->short_name);
                if ($manufacturer->short_name === '') {
                    $manufacturer->short_name = null;
                }
            }

            // slug автогенеруємо з name, якщо порожній
            if (empty($manufacturer->slug) && !empty($manufacturer->name)) {
                $manufacturer->slug = Str::slug($manufacturer->name);
            }

            // slug робимо унікальним
            if (!empty($manufacturer->slug)) {
                $manufacturer->slug = self::makeUniqueSlug($manufacturer->slug);
            }

            // ✅ sort_order:
            // якщо не задано (null або ''), ставимо max+1
            if (self::isEmptySortOrder($manufacturer->sort_order)) {
                $manufacturer->sort_order = self::nextSortOrder();
            }

            // дефолт для is_oem
            if ($manufacturer->is_oem === null) {
                $manufacturer->is_oem = false;
            }
        });

        static::updating(function (self $manufacturer) {
            // short_name чистимо
            if ($manufacturer->isDirty('short_name')) {
                $manufacturer->short_name = trim((string) ($manufacturer->short_name ?? ''));
                if ($manufacturer->short_name === '') {
                    $manufacturer->short_name = null;
                }
            }

            // якщо змінили name і slug порожній — оновимо slug
            if ($manufacturer->isDirty('name') && empty($manufacturer->slug) && !empty($manufacturer->name)) {
                $manufacturer->slug = Str::slug($manufacturer->name);
            }

            // якщо slug змінили — унікалізуємо
            if ($manufacturer->isDirty('slug') && !empty($manufacturer->slug)) {
                $manufacturer->slug = self::makeUniqueSlug($manufacturer->slug, $manufacturer->id);
            }

            // ✅ sort_order:
            // якщо юзер очистив поле (стало null/''), автоматично поставимо наступний
            if ($manufacturer->isDirty('sort_order') && self::isEmptySortOrder($manufacturer->sort_order)) {
                $manufacturer->sort_order = self::nextSortOrder();
            }
        });
    }

    private static function isEmptySortOrder(mixed $value): bool
    {
        // '': з форми, null: з моделі, інші значення норм
        return $value === null || $value === '';
    }

    private static function nextSortOrder(): int
    {
        $max = (int) (self::max('sort_order') ?? 0);
        return $max + 1;
    }

    private static function makeUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug);
        $candidate = $base;
        $i = 1;

        $query = self::query()->where('slug', $candidate);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        while ($query->exists()) {
            $candidate = $base . '-' . $i;
            $i++;

            $query = self::query()->where('slug', $candidate);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
        }

        return $candidate;
    }

    // =========================
    // COMPUTED INTERNAL URLS (Variant B)
    // =========================

    public function getInternalCatalogUrlUkAttribute(): string
    {
        $slug = (string) ($this->slug ?? '');
        return $slug !== '' ? "/uk/brands/{$slug}" : '';
    }

    public function getInternalCatalogUrlEnAttribute(): string
    {
        $slug = (string) ($this->slug ?? '');
        return $slug !== '' ? "/en/brands/{$slug}" : '';
    }

    public function getInternalCatalogUrlRuAttribute(): string
    {
        $slug = (string) ($this->slug ?? '');
        return $slug !== '' ? "/ru/brands/{$slug}" : '';
    }

    public function internalCatalogUrls(): array
    {
        return [
            'uk' => $this->internal_catalog_url_uk,
            'en' => $this->internal_catalog_url_en,
            'ru' => $this->internal_catalog_url_ru,
        ];
    }

    // =========================
    // HELPERS
    // =========================

    public function getDisplayNameAttribute(): string
    {
        $name = (string) ($this->name ?? '');
        $short = (string) ($this->short_name ?? '');

        return $short !== '' ? "{$short} — {$name}" : $name;
    }
}