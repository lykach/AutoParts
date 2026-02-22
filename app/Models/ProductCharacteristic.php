<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCharacteristic extends Model
{
    protected $table = 'product_characteristics';

    protected $fillable = [
        'product_id',
        'characteristic_id',
        'characteristic_value_id',

        'value_text_uk',
        'value_text_en',
        'value_text_ru',

        'value_number',
        'value_bool',

        'sort',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'characteristic_id' => 'integer',
        'characteristic_value_id' => 'integer',
        'value_number' => 'decimal:4',
        'value_bool' => 'boolean',
        'sort' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function characteristic(): BelongsTo
    {
        return $this->belongsTo(CharacteristicsProduct::class, 'characteristic_id');
    }

    public function characteristicValue(): BelongsTo
    {
        return $this->belongsTo(CharacteristicValue::class, 'characteristic_value_id');
    }

    /**
     * ✅ multi-value rows (тільки для select + is_multivalue=1)
     */
    public function multiValues(): HasMany
    {
        return $this->hasMany(ProductCharacteristicValue::class, 'product_id', 'product_id')
            ->where('characteristic_id', $this->characteristic_id)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function getDisplayValue(string $locale = 'uk'): string
    {
        $type = (string) ($this->characteristic?->type ?? '');

        if ($type === 'select') {
            // ✅ multi
            if ((bool) ($this->characteristic?->is_multivalue)) {
                $this->loadMissing(['multiValues.value', 'characteristic']);

                $vals = $this->multiValues
                    ->map(function (ProductCharacteristicValue $row) use ($locale) {
                        $v = $row->value;
                        if (! $v) return null;

                        $field = in_array($locale, ['uk', 'en', 'ru'], true) ? "value_{$locale}" : 'value_uk';

                        return (string) ($v->{$field} ?: $v->value_uk ?: $v->value_key ?: '');
                    })
                    ->filter()
                    ->values()
                    ->all();

                return implode(', ', $vals);
            }

            // ✅ single
            return (string) (
                $this->characteristicValue?->{"value_{$locale}"} ??
                $this->characteristicValue?->value_uk ??
                $this->characteristicValue?->value_key ??
                ''
            );
        }

        if ($type === 'number') {
            return $this->value_number !== null ? (string) $this->value_number : '';
        }

        if ($type === 'bool') {
            if ($this->value_bool === null) return '';
            return $this->value_bool ? 'Так' : 'Ні';
        }

        $locale = in_array($locale, ['uk', 'en', 'ru'], true) ? $locale : 'uk';
        $field = "value_text_{$locale}";

        return (string) ($this->{$field} ?: $this->value_text_uk ?: '');
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
    }
}