<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Country extends Model
{
    protected $fillable = [
        'code',
        'iso_code_2',
        'iso_code_3',
        'iso_code_numeric',
        'name_uk',
        'name_en',
        'name_ru',
        'currency_code',
        'flag_image',
        'is_group',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_group' => 'boolean',
        'is_active' => 'boolean',
        'iso_code_numeric' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Отримати локалізовану назву
     */
    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();

        return match ($locale) {
            'uk' => $this->name_uk ?? $this->name_en ?? $this->name_ru,
            'en' => $this->name_en ?? $this->name_uk ?? $this->name_ru,
            'ru' => $this->name_ru ?? $this->name_uk ?? $this->name_en,
            default => $this->name_uk ?? $this->name_en ?? $this->name_ru,
        } ?? ($this->code ?? (string) $this->id);
    }

    /**
     * Scope: тільки активні
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: з сортуванням
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name_uk');
    }

    /**
     * Scope: не групи
     */
    public function scopeNotGroups(Builder $query): Builder
    {
        return $query->where('is_group', false);
    }

    /**
     * ✅ URL прапора з public disk (storage/app/public)
     * flag_image зберігається як "flags/filename.webp" (бо directory('flags'))
     */
    public function getFlagUrlAttribute(): ?string
    {
        if (!$this->flag_image) {
            return null;
        }

        return Storage::disk('public')->url($this->flag_image);
    }
}
