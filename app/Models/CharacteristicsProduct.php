<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CharacteristicsProduct extends Model
{
    protected $table = 'characteristics_products';

    protected $fillable = [
        'code',
        'sort',

        'group_uk',
        'group_en',
        'group_ru',

        'name_uk',
        'name_en',
        'name_ru',

        'type',
        'is_multivalue',

        'unit',
        'decimals',
        'min_value',
        'max_value',

        'is_filterable',
        'is_visible',
        'is_important',

        'synonyms',

        // ❗ options більше не використовуємо як джерело істини
        // 'options',
    ];

    protected $casts = [
        'sort' => 'integer',
        'decimals' => 'integer',
        'min_value' => 'decimal:4',
        'max_value' => 'decimal:4',

        'is_multivalue' => 'boolean',
        'is_filterable' => 'boolean',
        'is_visible' => 'boolean',
        'is_important' => 'boolean',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(CharacteristicValue::class, 'characteristic_id')
            ->orderBy('sort')
            ->orderBy('id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'category_characteristic',
            'characteristic_id',
            'category_id'
        )
            ->withPivot(['sort', 'is_filterable', 'is_visible'])
            ->withTimestamps()
            ->orderByPivot('sort');
    }
}