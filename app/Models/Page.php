<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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
            if (blank($page->slug) && filled($page->name)) {
                $page->slug = static::generateUniqueSlug($page->name, $page->getKey());
            }

            if ($page->status === PageStatus::Published && blank($page->published_at)) {
                $page->published_at = now();
            }
        });
    }

    public static function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);

        if (blank($base)) {
            $base = 'page';
        }

        $slug = $base;
        $counter = 2;

        while (
            static::query()
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
        return $query->where('status', PageStatus::Published->value);
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
}