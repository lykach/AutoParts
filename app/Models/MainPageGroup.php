<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MainPageGroup extends Model
{
    protected $fillable = [
        'caption_uk',
        'caption_en',
        'caption_ru',
        'sort',
        'show_caption',
        'is_active',
    ];

    protected $casts = [
        'sort' => 'integer',
        'show_caption' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MainPageProduct::class, 'group_id')
            ->orderBy('sort')
            ->orderBy('id');
    }

    public function getCaptionAttribute(): string
    {
        return $this->caption_uk
            ?? $this->caption_en
            ?? $this->caption_ru
            ?? '';
    }

    protected static function booted(): void
    {
        static::creating(function (self $group): void {

            $maxSort = (int) static::query()->max('sort');

            $desiredSort = (int) ($group->sort ?: ($maxSort + 1));

            static::query()
                ->where('sort', '>=', $desiredSort)
                ->increment('sort');

            $group->sort = $desiredSort;
        });

        static::updating(function (self $group): void {

            if (! $group->isDirty('sort')) {
                return;
            }

            $oldSort = (int) $group->getOriginal('sort');
            $newSort = (int) $group->sort;

            if ($newSort < $oldSort) {

                static::query()
                    ->whereKeyNot($group->id)
                    ->whereBetween('sort', [$newSort, $oldSort - 1])
                    ->increment('sort');

            } else {

                static::query()
                    ->whereKeyNot($group->id)
                    ->whereBetween('sort', [$oldSort + 1, $newSort])
                    ->decrement('sort');

            }

        });

        static::deleted(function (self $group): void {

            static::query()
                ->where('sort', '>', $group->sort)
                ->decrement('sort');

        });
    }
}