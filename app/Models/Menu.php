<?php

namespace App\Models;

use App\Enums\MenuLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'code',
        'location',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'location' => MenuLocation::class,
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $menu) {
            if (blank($menu->code) && filled($menu->name)) {
                $menu->code = static::generateUniqueCode($menu->name, $menu->getKey());
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

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort');
    }

    public function rootItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->orderBy('sort');
    }
}