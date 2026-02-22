<?php

namespace App\Models;

use App\Support\CharacteristicValueKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharacteristicValue extends Model
{
    protected $table = 'characteristic_values';

    protected $fillable = [
        'characteristic_id',
        'value_key',
        'value_uk',
        'value_en',
        'value_ru',
        'value_number',
        'value_bool',
        'sort',
        'is_active',
    ];

    protected $casts = [
        'value_number' => 'decimal:4',
        'value_bool' => 'boolean',
        'sort' => 'integer',
        'is_active' => 'boolean',
        'characteristic_id' => 'integer',
    ];

    public function characteristic(): BelongsTo
    {
        return $this->belongsTo(CharacteristicsProduct::class, 'characteristic_id');
    }

    public function productCharacteristics(): HasMany
    {
        return $this->hasMany(ProductCharacteristic::class, 'characteristic_value_id');
    }

    public function getValueForLocale(string $locale = 'uk'): string
    {
        $locale = in_array($locale, ['uk', 'en', 'ru'], true) ? $locale : 'uk';
        $field = "value_{$locale}";

        return (string) ($this->{$field} ?: $this->value_uk ?: $this->value_key ?: '');
    }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            // ✅ Автопорядок, якщо не задано
            if ($m->sort === null || (int) $m->sort === 0) {
                $max = static::query()
                    ->where('characteristic_id', $m->characteristic_id)
                    ->max('sort');

                $m->sort = ((int) $max) + 1;
            }
        });

        static::saving(function (self $m) {
            if ($m->value_key !== null) {
                $m->value_key = trim((string) $m->value_key);
            }

            if (empty($m->value_key)) {
                $type = (string) ($m->characteristic?->type ?? '');

                if ($type === 'number') {
                    $decimals = (int) ($m->characteristic?->decimals ?? 0);
                    $m->value_key = CharacteristicValueKey::fromNumber($m->value_number, $decimals) ?? '';
                } elseif ($type === 'bool') {
                    $m->value_key = ($m->value_bool === null) ? '' : ($m->value_bool ? '1' : '0');
                } else {
                    $m->value_key = CharacteristicValueKey::fromText($m->value_uk, $m->value_en);
                }
            }
        });
    }
}