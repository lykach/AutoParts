<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Services\Cms\PageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use LogicException;

class Page extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'template',
        'status',
        'title_uk',
        'title_en',
        'title_ru',
        'excerpt_uk',
        'excerpt_en',
        'excerpt_ru',
        'content_uk',
        'content_en',
        'content_ru',
        'seo_title_uk',
        'seo_title_en',
        'seo_title_ru',
        'seo_description_uk',
        'seo_description_en',
        'seo_description_ru',
        'seo_keywords_uk',
        'seo_keywords_en',
        'seo_keywords_ru',
        'cover_image',
        'sort',
        'is_system',
        'show_in_sitemap',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'template' => PageTemplate::class,
        'status' => PageStatus::class,
        'seo_keywords_uk' => 'array',
        'seo_keywords_en' => 'array',
        'seo_keywords_ru' => 'array',
        'is_system' => 'boolean',
        'show_in_sitemap' => 'boolean',
        'sort' => 'integer',
        'published_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $page) {
            $page->name = trim((string) $page->name);

            $page->title_uk = $page->normalizeNullableString($page->title_uk);
            $page->title_en = $page->normalizeNullableString($page->title_en);
            $page->title_ru = $page->normalizeNullableString($page->title_ru);

            $page->excerpt_uk = $page->normalizeNullableString($page->excerpt_uk);
            $page->excerpt_en = $page->normalizeNullableString($page->excerpt_en);
            $page->excerpt_ru = $page->normalizeNullableString($page->excerpt_ru);

            $page->seo_title_uk = $page->normalizeNullableString($page->seo_title_uk);
            $page->seo_title_en = $page->normalizeNullableString($page->seo_title_en);
            $page->seo_title_ru = $page->normalizeNullableString($page->seo_title_ru);

            $page->seo_description_uk = $page->normalizeNullableString($page->seo_description_uk);
            $page->seo_description_en = $page->normalizeNullableString($page->seo_description_en);
            $page->seo_description_ru = $page->normalizeNullableString($page->seo_description_ru);

            $page->cover_image = $page->normalizeNullableString($page->cover_image);

            if (blank($page->slug) && filled($page->name)) {
                $page->slug = static::generateUniqueSlug($page->name, $page->getKey());
            } else {
                $page->slug = static::generateUniqueSlug((string) $page->slug, $page->getKey(), true);
            }

            if ($page->status === PageStatus::Published && blank($page->published_at)) {
                $page->published_at = now();
            }

            if ($page->exists) {
                $originalSlug = (string) $page->getOriginal('slug');

                if ($page->is_system && $originalSlug !== '' && $page->slug !== $originalSlug) {
                    $page->slug = $originalSlug;
                }
            }
        });

        static::saved(function (self $page) {
            static::flushCacheForRecord($page);
        });

        static::deleted(function (self $page) {
            static::flushCacheForRecord($page);
        });

        static::deleting(function (self $page) {
            if ($page->is_system) {
                throw new LogicException('Системну сторінку не можна видаляти.');
            }
        });
    }

    public static function generateUniqueSlug(string $value, ?int $ignoreId = null, bool $alreadySlugified = false): string
    {
        $base = $alreadySlugified ? Str::slug($value) : Str::slug($value);

        if (blank($base)) {
            $base = 'page';
        }

        $slug = $base;
        $counter = 2;

        while (
            static::query()
                ->withTrashed()
                ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', PageStatus::Published->value)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function getTitleAttribute(): string
    {
        return $this->title_uk
            ?: $this->title_en
            ?: $this->title_ru
            ?: $this->name;
    }

    public function getUrlAttribute(): string
    {
        return '/' . ltrim($this->slug, '/');
    }

    public function getCanBeDeletedAttribute(): bool
    {
        return ! $this->is_system;
    }

    public static function makeCacheKey(string $slug, bool $onlyPublished = true): string
    {
        $prefix = (string) config('cms.pages.cache_prefix', 'cms_page:');

        return $prefix . trim($slug, '/ ') . ':' . ($onlyPublished ? 'published' : 'all');
    }

    public static function flushCacheForSlug(string $slug): void
    {
        app(PageService::class)->flushBySlug($slug);
    }

    public static function flushCacheForRecord(self $page): void
    {
        static::flushCacheForSlug($page->slug);

        $originalSlug = (string) $page->getOriginal('slug');

        if ($originalSlug !== '' && $originalSlug !== $page->slug) {
            static::flushCacheForSlug($originalSlug);
        }
    }

    protected function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}