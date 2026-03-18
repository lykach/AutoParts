<?php

namespace App\Models;

use App\Enums\MenuItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id',
        'parent_id',
        'title_uk',
        'title_en',
        'title_ru',
        'type',
        'page_id',
        'url',
        'category_id',
        'manufacturer_id',
        'icon',
        'badge_text',
        'badge_color',
        'target_blank',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'type' => MenuItemType::class,
        'target_blank' => 'boolean',
        'is_active' => 'boolean',
        'sort' => 'integer',
        'menu_id' => 'integer',
        'parent_id' => 'integer',
        'page_id' => 'integer',
        'category_id' => 'integer',
        'manufacturer_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $item->normalizeFieldsByType();
            $item->normalizeTitles();
            $item->guardParent();
        });

        static::saved(function (self $item) {
            $item->flushRelatedMenuCache();
        });

        static::deleted(function (self $item) {
            $item->flushRelatedMenuCache();
        });
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function getTitleAttribute(): string
    {
        return $this->resolved_title;
    }

    public function getResolvedTitleAttribute(): string
    {
        if (filled($this->title_uk)) {
            return $this->title_uk;
        }

        if (filled($this->title_en)) {
            return $this->title_en;
        }

        if (filled($this->title_ru)) {
            return $this->title_ru;
        }

        return match ($this->type) {
            MenuItemType::Page => $this->page?->title_uk
                ?: $this->page?->title_en
                ?: $this->page?->title_ru
                ?: $this->page?->name
                ?: 'Без назви',
            MenuItemType::Category => $this->category?->name_uk
                ?: $this->category?->name_en
                ?: $this->category?->name_ru
                ?: 'Без назви',
            MenuItemType::Manufacturer => $this->manufacturer?->name
                ?: 'Без назви',
            default => 'Без назви',
        };
    }

    public function getResolvedUrlAttribute(): ?string
    {
        return match ($this->type) {
            MenuItemType::Page => $this->page?->url,
            MenuItemType::Url => filled($this->url) ? trim($this->url) : null,
            MenuItemType::Category => $this->category
                ? '/' . ltrim((string) $this->category->full_url_path, '/')
                : null,
            MenuItemType::Manufacturer => $this->manufacturer && filled($this->manufacturer->slug)
                ? '/manufacturer/' . ltrim((string) $this->manufacturer->slug, '/')
                : null,
            default => null,
        };
    }

    public function flushRelatedMenuCache(): void
    {
        if ($this->menu) {
            Menu::flushCacheForRecord($this->menu);
            return;
        }

        if ($this->menu_id) {
            $menu = Menu::query()->find($this->menu_id);

            if ($menu) {
                Menu::flushCacheForRecord($menu);
            }
        }
    }

    protected function normalizeFieldsByType(): void
    {
        $type = $this->type instanceof MenuItemType
            ? $this->type
            : MenuItemType::tryFrom((string) $this->type);

        if (! $type) {
            return;
        }

        if (filled($this->url)) {
            $this->url = trim((string) $this->url);
        }

        match ($type) {
            MenuItemType::Page => $this->clearNonPageFields(),
            MenuItemType::Url => $this->clearNonUrlFields(),
            MenuItemType::Category => $this->clearNonCategoryFields(),
            MenuItemType::Manufacturer => $this->clearNonManufacturerFields(),
        };
    }

    protected function normalizeTitles(): void
    {
        $this->title_uk = $this->normalizeNullableString($this->title_uk);
        $this->title_en = $this->normalizeNullableString($this->title_en);
        $this->title_ru = $this->normalizeNullableString($this->title_ru);
        $this->icon = $this->normalizeNullableString($this->icon);
        $this->badge_text = $this->normalizeNullableString($this->badge_text);
        $this->badge_color = $this->normalizeNullableString($this->badge_color);

        if ($this->type === MenuItemType::Page || $this->type === MenuItemType::Page->value) {
            if ($this->hasAnyTitle()) {
                return;
            }

            $this->title_uk = $this->page?->title_uk
                ?: $this->page?->title_en
                ?: $this->page?->title_ru
                ?: $this->page?->name;
        }

        if ($this->type === MenuItemType::Category || $this->type === MenuItemType::Category->value) {
            if ($this->hasAnyTitle()) {
                return;
            }

            $this->title_uk = $this->category?->name_uk
                ?: $this->category?->name_en
                ?: $this->category?->name_ru;
        }

        if ($this->type === MenuItemType::Manufacturer || $this->type === MenuItemType::Manufacturer->value) {
            if ($this->hasAnyTitle()) {
                return;
            }

            $this->title_uk = $this->manufacturer?->name;
        }
    }

    protected function guardParent(): void
    {
        if (blank($this->parent_id)) {
            $this->parent_id = null;
            return;
        }

        if ($this->exists && $this->parent_id === $this->getKey()) {
            $this->parent_id = null;
            return;
        }

        $parent = self::query()->find($this->parent_id);

        if (! $parent || $parent->menu_id !== $this->menu_id) {
            $this->parent_id = null;
        }
    }

    protected function clearNonPageFields(): void
    {
        $this->url = null;
        $this->category_id = null;
        $this->manufacturer_id = null;
    }

    protected function clearNonUrlFields(): void
    {
        $this->page_id = null;
        $this->category_id = null;
        $this->manufacturer_id = null;
    }

    protected function clearNonCategoryFields(): void
    {
        $this->page_id = null;
        $this->url = null;
        $this->manufacturer_id = null;
    }

    protected function clearNonManufacturerFields(): void
    {
        $this->page_id = null;
        $this->url = null;
        $this->category_id = null;
    }

    protected function hasAnyTitle(): bool
    {
        return filled($this->title_uk)
            || filled($this->title_en)
            || filled($this->title_ru);
    }

    protected function normalizeNullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}