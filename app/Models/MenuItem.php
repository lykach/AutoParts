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
        return $this->title_uk
            ?: $this->title_en
            ?: $this->title_ru
            ?: '—';
    }

    public function getResolvedUrlAttribute(): ?string
    {
        return match ($this->type) {
            MenuItemType::Page => $this->page?->url,
            MenuItemType::Url => $this->url,
            MenuItemType::Category => $this->category ? '/' . ltrim((string) $this->category->full_url_path, '/') : null,
            MenuItemType::Manufacturer => $this->manufacturer ? '/manufacturer/' . $this->manufacturer->slug : null,
            default => null,
        };
    }
}