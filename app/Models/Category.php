<?php

namespace App\Models;

use App\Services\Category\CategoryTreeService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'depth',
        'order',
        'tecdoc_id',
        'slug',
        'path_ids',
        'path_slugs',
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
        'children_count',
        'products_direct_count',
        'products_total_count',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'depth' => 'integer',
        'order' => 'integer',
        'tecdoc_id' => 'integer',
        'is_active' => 'boolean',
        'is_leaf' => 'boolean',
        'is_container' => 'boolean',
        'children_count' => 'integer',
        'products_direct_count' => 'integer',
        'products_total_count' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('order')
            ->orderBy('id');
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

    public function hasProducts(): bool
    {
        return $this->products()->exists();
    }

    public function hasCharacteristics(): bool
    {
        return $this->characteristics()->exists();
    }

    public function hasChildren(): bool
    {
        if ($this->children_count !== null) {
            return (int) $this->children_count > 0;
        }

        return $this->children()->exists();
    }

    public function canHaveChildren(): bool
    {
        return ! $this->hasProducts() && ! $this->hasCharacteristics();
    }

    public function canHaveProducts(): bool
    {
        if ((bool) $this->is_container) {
            return false;
        }

        if ($this->hasChildren()) {
            return false;
        }

        return true;
    }

    public function canBeContainer(): bool
    {
        return is_null($this->parent_id)
            && ! $this->hasProducts()
            && ! $this->hasCharacteristics();
    }

    public function descendantIds(int $maxDepth = 100): array
    {
        if (! $this->exists) {
            return [];
        }

        if (! empty($this->path_ids)) {
            $prefix = trim((string) $this->path_ids, '/');

            return static::query()
                ->where('id', '!=', $this->id)
                ->where('path_ids', 'like', $prefix . '/%')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
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

        return array_values(array_unique(array_map('intval', $result)));
    }

    public function wouldCreateCycleWithParent(?int $newParentId): bool
    {
        if ($newParentId === null) {
            return false;
        }

        if (! $this->exists) {
            return false;
        }

        if ((int) $newParentId === (int) $this->id) {
            return true;
        }

        if (! empty($this->path_ids)) {
            $newParent = static::query()->find($newParentId);

            if ($newParent && ! empty($newParent->path_ids)) {
                $selfPath = trim((string) $this->path_ids, '/');
                $newParentPath = trim((string) $newParent->path_ids, '/');

                return $newParentPath === $selfPath || str_starts_with($newParentPath, $selfPath . '/');
            }
        }

        $current = static::find($newParentId);
        $maxDepth = 100;

        while ($current && $maxDepth > 0) {
            if ((int) $current->id === (int) $this->id) {
                return true;
            }

            if ($current->parent_id === null) {
                break;
            }

            $current = static::find((int) $current->parent_id);
            $maxDepth--;
        }

        return false;
    }

    public static function nextOrderForParent(?int $parentId): int
    {
        $query = static::query();

        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        $max = $query->max('order');

        return ((int) $max) + 1;
    }

    public static function normalizeSiblingOrder(?int $parentId): void
    {
        app(CategoryTreeService::class)->normalizeSiblingOrder($parentId);
    }

    public function moveUp(): void
    {
        DB::transaction(function () {
            static::normalizeSiblingOrder($this->parent_id);

            $fresh = $this->fresh();

            $prev = static::query()
                ->when(
                    $fresh->parent_id === null,
                    fn ($q) => $q->whereNull('parent_id'),
                    fn ($q) => $q->where('parent_id', $fresh->parent_id)
                )
                ->where('order', '<', $fresh->order)
                ->orderByDesc('order')
                ->orderByDesc('id')
                ->first();

            if (! $prev) {
                return;
            }

            $currentOrder = (int) $fresh->order;
            $prevOrder = (int) $prev->order;

            DB::table('categories')->where('id', $fresh->id)->update(['order' => $prevOrder]);
            DB::table('categories')->where('id', $prev->id)->update(['order' => $currentOrder]);
        });

        app(CategoryTreeService::class)->afterSave(
            categoryId: (int) $this->id,
            oldParentId: $this->parent_id,
            newParentId: $this->parent_id,
        );

        $this->refresh();
    }

    public function moveDown(): void
    {
        DB::transaction(function () {
            static::normalizeSiblingOrder($this->parent_id);

            $fresh = $this->fresh();

            $next = static::query()
                ->when(
                    $fresh->parent_id === null,
                    fn ($q) => $q->whereNull('parent_id'),
                    fn ($q) => $q->where('parent_id', $fresh->parent_id)
                )
                ->where('order', '>', $fresh->order)
                ->orderBy('order')
                ->orderBy('id')
                ->first();

            if (! $next) {
                return;
            }

            $currentOrder = (int) $fresh->order;
            $nextOrder = (int) $next->order;

            DB::table('categories')->where('id', $fresh->id)->update(['order' => $nextOrder]);
            DB::table('categories')->where('id', $next->id)->update(['order' => $currentOrder]);
        });

        app(CategoryTreeService::class)->afterSave(
            categoryId: (int) $this->id,
            oldParentId: $this->parent_id,
            newParentId: $this->parent_id,
        );

        $this->refresh();
    }

    public function moveToParent(?int $newParentId): void
    {
        $oldParentId = $this->parent_id;

        DB::transaction(function () use ($newParentId) {
            $this->refresh();

            if ($newParentId !== null) {
                $parent = static::query()->find($newParentId);

                if (! $parent) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'Батьківська категорія не знайдена.',
                    ]);
                }

                if (! $parent->canHaveChildren()) {
                    throw ValidationException::withMessages([
                        'parent_id' => "Неможливо: категорія '{$parent->name_uk}' має товари або характеристики і не може мати підкатегорій.",
                    ]);
                }
            }

            if ((bool) $this->is_container && $newParentId !== null) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Контейнерна категорія може бути тільки кореневою.',
                ]);
            }

            if ($this->wouldCreateCycleWithParent($newParentId)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Неможливо вибрати батьківську категорію: це створить циклічну структуру.',
                ]);
            }

            DB::table('categories')
                ->where('id', $this->id)
                ->update([
                    'parent_id' => $newParentId,
                    'order' => static::nextOrderForParent($newParentId),
                ]);
        });

        app(CategoryTreeService::class)->afterSave(
            categoryId: (int) $this->id,
            oldParentId: $oldParentId,
            newParentId: $newParentId,
        );

        $this->refresh();
    }

    public function makeRoot(): void
    {
        $this->moveToParent(null);
    }

    public function subtreeQuery(bool $includeSelf = true)
    {
        $prefix = trim((string) $this->path_ids, '/');

        return static::query()->where(function ($q) use ($includeSelf, $prefix) {
            if ($includeSelf) {
                $q->where('path_ids', $prefix)
                    ->orWhere('path_ids', 'like', $prefix . '/%');
            } else {
                $q->where('path_ids', 'like', $prefix . '/%');
            }
        });
    }

    public function subtree(bool $includeSelf = true): EloquentCollection
    {
        return $this->subtreeQuery($includeSelf)
            ->orderBy('depth')
            ->orderBy('path_ids')
            ->get();
    }

    public function breadcrumbs(): EloquentCollection
    {
        $ids = $this->breadcrumb_ids;

        if (empty($ids)) {
            return new EloquentCollection();
        }

        $items = static::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $ordered = [];

        foreach ($ids as $id) {
            if (isset($items[$id])) {
                $ordered[] = $items[$id];
            }
        }

        return new EloquentCollection($ordered);
    }

    public function isDescendantOf(Category|int $category): bool
    {
        $parentId = $category instanceof Category ? (int) $category->id : (int) $category;

        if (! $this->path_ids) {
            return false;
        }

        $segments = explode('/', trim((string) $this->path_ids, '/'));
        array_pop($segments);

        return in_array((string) $parentId, $segments, true);
    }

    public function getFullUrlPathAttribute(): string
    {
        if (! empty($this->path_slugs)) {
            return (string) $this->path_slugs;
        }

        $path = [$this->slug];
        $current = $this;
        $maxDepth = 50;

        while ($current->parent_id !== null && $maxDepth > 0) {
            $parent = static::find((int) $current->parent_id);

            if (! $parent) {
                break;
            }

            array_unshift($path, $parent->slug);
            $current = $parent;
            $maxDepth--;
        }

        return implode('/', $path);
    }

    public function getBreadcrumbIdsAttribute(): array
    {
        if (empty($this->path_ids)) {
            return [$this->id];
        }

        return array_map('intval', explode('/', trim((string) $this->path_ids, '/')));
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLeaf($query)
    {
        return $query->where('is_leaf', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeContainer($query)
    {
        return $query->where('is_container', true);
    }

    protected static function booted(): void
    {
        static::creating(function (self $category) {
            if (blank($category->slug) && filled($category->name_uk)) {
                $category->slug = static::generateUniqueSlug((string) $category->name_uk);
            }

            if ($category->order === null || (int) $category->order <= 0) {
                $category->order = static::nextOrderForParent($category->parent_id);
            }
        });

        static::saving(function (self $category) {
            if ($category->id && $category->parent_id !== null && (int) $category->parent_id === (int) $category->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Категорія не може бути батьком сама собі.',
                ]);
            }

            if ($category->id && $category->isDirty('parent_id')) {
                if ($category->wouldCreateCycleWithParent($category->parent_id)) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'Неможливо вибрати батьківську категорію: це створить циклічну структуру.',
                    ]);
                }
            }

            if ($category->parent_id !== null) {
                $parent = static::find($category->parent_id);

                if (! $parent) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'Батьківська категорія не знайдена.',
                    ]);
                }

                if (! $parent->canHaveChildren()) {
                    throw ValidationException::withMessages([
                        'parent_id' => "Неможливо: категорія '{$parent->name_uk}' має товари або характеристики і не може мати підкатегорій.",
                    ]);
                }
            }

            if ((bool) $category->is_container && $category->parent_id !== null) {
                throw ValidationException::withMessages([
                    'is_container' => 'Контейнерна категорія може бути тільки кореневою.',
                    'parent_id' => 'Контейнерна категорія може бути тільки кореневою.',
                ]);
            }

            if ((bool) $category->is_container && $category->exists) {
                if ($category->hasProducts() || $category->hasCharacteristics()) {
                    throw ValidationException::withMessages([
                        'is_container' => "Неможливо зробити контейнером: категорія '{$category->name_uk}' має товари або характеристики.",
                    ]);
                }
            }
        });

        static::updating(function (self $category) {
            if ($category->isDirty('name_uk') && filled($category->name_uk)) {
                $newSlug = Str::slug((string) $category->name_uk);

                if ($newSlug !== $category->slug) {
                    $category->slug = static::generateUniqueSlug(
                        (string) $category->name_uk,
                        $category->id
                    );
                }
            }

            if ($category->isDirty('parent_id') || $category->isDirty('order')) {
                if ($category->order === null || (int) $category->order <= 0) {
                    $category->order = static::nextOrderForParent($category->parent_id);
                }
            }
        });

        static::saved(function (self $category) {
            app(CategoryTreeService::class)->afterSave(
                categoryId: (int) $category->id,
                oldParentId: $category->getOriginal('parent_id'),
                newParentId: $category->parent_id,
            );
        });

        static::deleted(function (self $category) {
            app(CategoryTreeService::class)->afterDelete(
                oldParentId: $category->parent_id
            );
        });
    }

    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $slug = $slug !== '' ? $slug : 'category';

        $original = $slug;
        $i = 1;

        while (
            static::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $original . '-' . $i;
            $i++;
        }

        return $slug;
    }
}