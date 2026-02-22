<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDetail extends Model
{
    protected $table = 'product_details';

    protected $fillable = [
        'product_id',
        'name_uk', 'name_en', 'name_ru',
        'value_uk', 'value_en', 'value_ru',
        'sort',
        'source',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'sort' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getNameForLocale(string $locale = 'uk'): string
    {
        $locale = in_array($locale, ['uk', 'en', 'ru'], true) ? $locale : 'uk';
        $field = "name_{$locale}";

        return (string) ($this->{$field} ?: $this->name_uk ?: $this->name_en ?: $this->name_ru ?: '');
    }

    public function getValueForLocale(string $locale = 'uk'): string
    {
        $locale = in_array($locale, ['uk', 'en', 'ru'], true) ? $locale : 'uk';
        $field = "value_{$locale}";

        return (string) ($this->{$field} ?: $this->value_uk ?: $this->value_en ?: $this->value_ru ?: '');
    }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            // ✅ Автопорядок, якщо не задано
            if ($m->sort === null || (int) $m->sort === 0) {
                $max = static::query()
                    ->where('product_id', $m->product_id)
                    ->max('sort');

                $m->sort = ((int) $max) + 1;
            }
        });

        static::saving(function (self $m) {
            foreach (['name_uk','name_en','name_ru','value_uk','value_en','value_ru'] as $f) {
                if ($m->{$f} !== null) {
                    $m->{$f} = trim((string) $m->{$f});
                    if ($m->{$f} === '') $m->{$f} = null;
                }
            }
        });
    }
}