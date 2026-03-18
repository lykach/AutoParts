<?php

namespace App\Models;

use App\Enums\MenuLocation;
use App\Services\Cms\MenuService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'code',
        'location',
        'is_system',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'location' => MenuLocation::class,
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $menu) {
            $menu->name = trim((string) $menu->name);
            $menu->code = $menu->normalizeCode($menu->code, $menu->name);

            if ($menu->exists) {
                $originalCode = (string) $menu->getOriginal('code');

                if ($menu->is_system && $originalCode !== '' && $menu->code !== $originalCode) {
                    $menu->code = $originalCode;
                }
            }
        });

        static::saved(function (self $menu) {
            static::flushCacheForRecord($menu);
        });

        static::deleted(function (self $menu) {
            static::flushCacheForRecord($menu);
        });

        static::deleting(function (self $menu) {
            if ($menu->is_system) {
                throw new LogicException('Системне меню не можна видаляти.');
            }

            if ($menu->items()->exists()) {
                throw new LogicException('Меню з пунктами не можна видаляти. Спочатку видаліть або перенесіть пункти меню.');
            }
        });
    }

    public static function generateUniqueCode(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);

        if (blank($base)) {
            $base = 'menu';
        }

        $code = $base;
        $counter = 2;

        while (
            static::query()
                ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
                ->where('code', $code)
                ->exists()
        ) {
            $code = $base . '-' . $counter;
            $counter++;
        }

        return $code;
    }

    public function normalizeCode(?string $code, ?string $name = null): string
    {
        $code = trim((string) $code);

        if ($code !== '') {
            $normalized = Str::slug($code);

            if ($normalized === '') {
                $normalized = 'menu';
            }

            return static::generateUniqueCode($normalized, $this->getKey());
        }

        $name = trim((string) $name);

        return static::generateUniqueCode($name !== '' ? $name : 'menu', $this->getKey());
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort')->orderBy('id');
    }

    public function rootItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->orderBy('sort')
            ->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getCanBeDeletedAttribute(): bool
    {
        return ! $this->is_system && ! $this->items()->exists();
    }

    public static function makeCacheKey(string $code, bool $onlyActive = true): string
    {
        $prefix = (string) config('cms.menus.cache_prefix', 'cms_menu:');

        return $prefix . $code . ':' . ($onlyActive ? 'active' : 'all');
    }

    public static function flushCacheForCode(string $code): void
    {
        app(MenuService::class)->flushByCode($code);
    }

    public static function flushCacheForRecord(self $menu): void
    {
        static::flushCacheForCode($menu->code);

        $originalCode = (string) $menu->getOriginal('code');

        if ($originalCode !== '' && $originalCode !== $menu->code) {
            static::flushCacheForCode($originalCode);
        }
    }
}