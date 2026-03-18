<?php

namespace App\Services\Cms;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;

class PageService
{
    public function getBySlug(string $slug, bool $onlyPublished = true): ?array
    {
        $slug = trim($slug, '/ ');

        if ($slug === '') {
            return null;
        }

        $cacheKey = Page::makeCacheKey($slug, $onlyPublished);
        $ttl = (int) config('cms.pages.cache_ttl', 0);

        $resolver = function () use ($slug, $onlyPublished): ?array {
            $query = Page::query()->where('slug', $slug);

            if ($onlyPublished) {
                $query->where('status', PageStatus::Published->value)
                    ->where(function ($q) {
                        $q->whereNull('published_at')
                            ->orWhere('published_at', '<=', now());
                    });
            }

            /** @var Page|null $page */
            $page = $query->first();

            if (! $page) {
                return null;
            }

            return $this->toArray($page);
        };

        if ($ttl > 0) {
            return Cache::remember($cacheKey, now()->addMinutes($ttl), $resolver);
        }

        return Cache::rememberForever($cacheKey, $resolver);
    }

    public function getModelBySlug(string $slug, bool $onlyPublished = true): ?Page
    {
        $slug = trim($slug, '/ ');

        if ($slug === '') {
            return null;
        }

        $query = Page::query()->where('slug', $slug);

        if ($onlyPublished) {
            $query->where('status', PageStatus::Published->value)
                ->where(function ($q) {
                    $q->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                });
        }

        return $query->first();
    }

    public function getSystemPage(string $slug, bool $onlyPublished = true): ?array
    {
        $page = $this->getBySlug($slug, $onlyPublished);

        if (! $page || ! $page['is_system']) {
            return null;
        }

        return $page;
    }

    public function flushBySlug(string $slug): void
    {
        Cache::forget(Page::makeCacheKey($slug, true));
        Cache::forget(Page::makeCacheKey($slug, false));
    }

    public function flushPage(Page $page): void
    {
        $this->flushBySlug($page->slug);
    }

    public function toArray(Page $page): array
    {
        return [
            'id' => $page->id,
            'name' => $page->name,
            'slug' => $page->slug,
            'url' => $page->url,
            'template' => $page->template?->value ?? $page->template,
            'status' => $page->status?->value ?? $page->status,
            'title' => $page->title,
            'title_uk' => $page->title_uk,
            'title_en' => $page->title_en,
            'title_ru' => $page->title_ru,
            'excerpt_uk' => $page->excerpt_uk,
            'excerpt_en' => $page->excerpt_en,
            'excerpt_ru' => $page->excerpt_ru,
            'content_uk' => $page->content_uk,
            'content_en' => $page->content_en,
            'content_ru' => $page->content_ru,
            'seo_title_uk' => $page->seo_title_uk,
            'seo_title_en' => $page->seo_title_en,
            'seo_title_ru' => $page->seo_title_ru,
            'seo_description_uk' => $page->seo_description_uk,
            'seo_description_en' => $page->seo_description_en,
            'seo_description_ru' => $page->seo_description_ru,
            'seo_keywords_uk' => $page->seo_keywords_uk ?? [],
            'seo_keywords_en' => $page->seo_keywords_en ?? [],
            'seo_keywords_ru' => $page->seo_keywords_ru ?? [],
            'cover_image' => $page->cover_image,
            'is_system' => (bool) $page->is_system,
            'show_in_sitemap' => (bool) $page->show_in_sitemap,
            'published_at' => $page->published_at?->toDateTimeString(),
            'updated_at' => $page->updated_at?->toDateTimeString(),
        ];
    }
}