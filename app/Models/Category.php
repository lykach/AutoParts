<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use SolutionForest\FilamentTree\Concern\ModelTree;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\CharacteristicsProduct;

class Category extends Model
{
    use ModelTree;

    protected $fillable = [
        'parent_id',
        'order',
        'tecdoc_id',
        'slug',
        'name_uk',
        'name_en',
        'name_ru',
        'description_uk',
        'description_en',
        'description_ru',
        'meta_title_uk',
        'meta_title_en',
        'meta_title_ru',
        'meta_description_uk',
        'meta_description_en',
        'meta_description_ru',
        'image',
        'is_active',
        'is_leaf',
        'is_container',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_leaf' => 'boolean',
        'is_container' => 'boolean',
        'order' => 'integer',
        'parent_id' => 'integer',
    ];

    // ============================================
    // FILAMENT-TREE
    // ============================================

    public function determineOrderColumnName(): string
    {
        return 'order';
    }

    public function determineTitleColumnName(): string
    {
        return 'name_uk';
    }

    public function determineParentColumnName(): string
    {
        return 'parent_id';
    }

    public static function defaultParentKey()
    {
        return -1;
    }

    public static function defaultChildrenKeyName(): string
    {
        return 'children';
    }

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id')->where('id', '!=', -1);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function mirrorsAsSource(): HasMany
    {
        return $this->hasMany(CategoryMirror::class, 'source_category_id');
    }

    public function mirrorsAsParent(): HasMany
    {
        return $this->hasMany(CategoryMirror::class, 'parent_category_id');
    }
	
	public function characteristics(): BelongsToMany
    {
        return $this->belongsToMany(
            CharacteristicsProduct::class,
            'category_characteristic',
            'category_id',
            'characteristic_id'
        )
            ->withPivot(['sort', 'is_visible', 'is_filterable'])
            ->withTimestamps()
            ->orderByPivot('sort');
    }

    // ============================================
    // BUSINESS RULES
    // ============================================

    public function hasProducts(): bool
    {
        return $this->products()->exists();
    }

    public function canHaveChildren(): bool
    {
        // якщо є товари — забороняємо підкатегорії
        return ! $this->hasProducts();
    }

    public function canHaveProducts(): bool
    {
        // контейнер — тільки для структури/дзеркал
        if ($this->is_container) {
            return false;
        }

        // якщо є діти — це не кінцева категорія
        if ($this->children()->exists()) {
            return false;
        }

        return true;
    }

    // ============================================
    // HELPERS
    // ============================================

    public function descendantIds(int $maxDepth = 50): array
    {
        if (! $this->exists) {
            return [];
        }

        $result = [];
        $level = 0;
        $queue = [$this->id];

        while (! empty($queue) && $level < $maxDepth) {
            $children = static::query()
                ->whereIn('parent_id', $queue)
                ->pluck('id')
                ->all();

            if (empty($children)) {
                break;
            }

            $result = array_merge($result, $children);
            $queue = $children;
            $level++;
        }

        return array_values(array_unique($result));
    }

    public function wouldCreateCycleWithParent(?int $newParentId): bool
    {
        if ($newParentId === null || $newParentId === -1) {
            return false;
        }

        if (! $this->exists) {
            return false;
        }

        if ((int) $newParentId === (int) $this->id) {
            return true;
        }

        $current = static::find((int) $newParentId);

        $maxDepth = 50;
        while ($current && $maxDepth > 0) {
            if ((int) $current->id === (int) $this->id) {
                return true;
            }

            if ($current->parent_id === null || (int) $current->parent_id === -1) {
                break;
            }

            $current = static::find((int) $current->parent_id);
            $maxDepth--;
        }

        return false;
    }

    public function getAllUrlPaths(): array
    {
        $paths = [];

        $paths[] = [
            'path' => $this->full_url_path,
            'is_canonical' => true,
            'mirror_id' => null,
        ];

        foreach ($this->mirrorsAsSource as $mirror) {
            if ($mirror->is_active) {
                $paths[] = [
                    'path' => $mirror->full_url_path,
                    'is_canonical' => false,
                    'mirror_id' => $mirror->id,
                ];
            }
        }

        return $paths;
    }

    // ============================================
    // BOOT EVENTS
    // ============================================

    protected static function booted()
    {
        static::creating(function (self $category) {
            if (empty($category->slug) && ! empty($category->name_uk)) {
                $category->slug = Str::slug($category->name_uk);

                $originalSlug = $category->slug;
                $count = 1;

                while (static::where('slug', $category->slug)->exists()) {
                    $category->slug = $originalSlug . '-' . $count;
                    $count++;
                }
            }

            if ($category->order === null || $category->order === 0) {
                $parentId = $category->parent_id ?? -1;
                $maxOrder = static::where('parent_id', $parentId)->max('order') ?? 0;
                $category->order = $maxOrder + 1;
            }

            if ($category->parent_id === null) {
                $category->parent_id = -1;
            }
        });

        static::saving(function (self $category) {
            if ($category->parent_id === null) {
                $category->parent_id = -1;
            }

            if ($category->id && (int) $category->parent_id === (int) $category->id) {
                throw new \Exception("Категорія не може бути батьком сама собі!");
            }

            if ($category->id && $category->isDirty('parent_id')) {
                if ($category->wouldCreateCycleWithParent((int) $category->parent_id)) {
                    throw new \Exception("Неможливо вибрати батьківську категорію: це створить циклічну структуру!");
                }
            }

            // ✅ Якщо робимо контейнером — товари в цій категорії бути не повинні
            // (не видаляємо автоматично, але не даємо увімкнути, якщо вже є товари)
            if ($category->isDirty('is_container') && $category->is_container) {
                if ($category->hasProducts()) {
                    throw new \Exception("Неможливо зробити контейнером: категорія має товари!");
                }
            }
        });

        static::updating(function (self $category) {
            if ($category->isDirty('name_uk')) {
                $newSlug = Str::slug($category->name_uk);

                if ($newSlug !== $category->slug) {
                    $originalSlug = $newSlug;
                    $count = 1;

                    while (static::where('slug', $newSlug)->where('id', '!=', $category->id)->exists()) {
                        $newSlug = $originalSlug . '-' . $count;
                        $count++;
                    }

                    $category->slug = $newSlug;
                }
            }

            if ($category->parent_id === null) {
                $category->parent_id = -1;
            }
        });

        static::saved(function (self $category) {
            $newParentId = (int) ($category->parent_id ?? -1);
            $oldParentId = (int) ($category->getOriginal('parent_id') ?? -1);

            // 1) поточна
            $category->updateLeafStatus();

            // 2) новий батько
            if ($newParentId !== -1) {
                static::find($newParentId)?->updateLeafStatus();
            }

            // 3) старий батько (якщо змінився)
            if ($oldParentId !== -1 && $oldParentId !== $newParentId) {
                static::find($oldParentId)?->updateLeafStatus();
            }
        });
    }

    public function updateLeafStatus(): void
    {
        $hasChildren = static::where('parent_id', $this->id)->exists();
        $isLeaf = ! $hasChildren;

        if ($this->is_leaf !== $isLeaf) {
            $this->withoutEvents(function () use ($isLeaf) {
                $this->update(['is_leaf' => $isLeaf]);
            });
        }
    }

    // ============================================
    // ACCESSORS
    // ============================================

    public function getFullUrlPathAttribute(): string
    {
        return once(function () {
            $path = [$this->slug];
            $current = $this;
            $maxDepth = 12;

            while ($current->parent_id && $current->parent_id !== -1 && $maxDepth > 0) {
                $parent = static::find($current->parent_id);
                if (! $parent) {
                    break;
                }

                array_unshift($path, $parent->slug);
                $current = $parent;
                $maxDepth--;
            }

            return implode('/', $path);
        });
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeLeaf($query) { return $query->where('is_leaf', true); }
    public function scopeRoot($query) { return $query->where('parent_id', -1); }
    public function scopeContainer($query) { return $query->where('is_container', true); }
}