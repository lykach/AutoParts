<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryMirror extends Model
{
    protected $fillable = [
        'parent_category_id',
        'source_category_id',
        'custom_name_uk',
        'custom_name_en',
        'custom_name_ru',
        'custom_slug',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'parent_category_id' => 'integer',
        'source_category_id' => 'integer',
    ];

    /**
     * Батьківська категорія (під якою показуємо дублікат)
     */
    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_category_id');
    }

    /**
     * Джерело (яку оригінальну категорію ми дублюємо)
     */
    public function sourceCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'source_category_id');
    }

    protected static function booted()
    {
        static::creating(function (self $mirror) {
            // Автоматичне призначення sort_order при створенні
            if (!isset($mirror->sort_order)) {
                $maxOrder = static::where('parent_category_id', $mirror->parent_category_id)
                        ->max('sort_order') ?? 0;

                $mirror->sort_order = $maxOrder + 1;
            }

            // Автоматичний slug, якщо порожній
            // ✅ НЕ покладаємось на relationship у creating
            if (empty($mirror->custom_slug) && $mirror->source_category_id) {
                $mirror->custom_slug = Category::whereKey($mirror->source_category_id)->value('slug') ?? null;
            }
        });

        static::saving(function (self $mirror) {
            // 1) Неможливо дублювати категорію саму під себе
            if ((int) $mirror->parent_category_id === (int) $mirror->source_category_id) {
                throw new \Exception('Неможливо дублювати категорію саму під себе!');
            }

            // 2) Перевірка на циклічність
            if ($mirror->wouldCreateCycle()) {
                throw new \Exception(
                    'Це створить циклічне посилання (категорія-джерело є предком батьківської категорії)!'
                );
            }
        });
    }

    /**
     * Перевірка на циклічні посилання:
     * sourceId не має бути предком parentId
     */
    public function wouldCreateCycle(): bool
    {
        $parentId = (int) $this->parent_category_id;
        $sourceId = (int) $this->source_category_id;

        if ($parentId <= 0 || $sourceId <= 0) {
            return false;
        }

        $current = Category::find($parentId);

        // Захист від нескінченного циклу на випадок битих даних
        $maxDepth = 50;

        while ($current && $maxDepth > 0) {
            if ((int) $current->id === $sourceId) {
                return true;
            }

            $current = $current->parent; // припускаємо, що Category має parent()
            $maxDepth--;
        }

        return false;
    }

    /**
     * Отримати назву для відображення (враховуючи локалізацію)
     */
    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        $customNameColumn = "custom_name_{$locale}";

        if (!empty($this->{$customNameColumn})) {
            return $this->{$customNameColumn};
        }

        return $this->sourceCategory?->name_uk
            ?? $this->sourceCategory?->name
            ?? 'Без назви';
    }

    /**
     * Отримати повний URL шлях
     */
    public function getFullUrlPathAttribute(): string
    {
        $parentPath = $this->parentCategory ? $this->parentCategory->full_url_path : '';
        $slug = $this->custom_slug ?: ($this->sourceCategory?->slug ?? '');

        return trim($parentPath . '/' . $slug, '/');
    }

    /**
     * Scope для активних дублікатів
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
