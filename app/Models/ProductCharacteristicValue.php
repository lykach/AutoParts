<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCharacteristicValue extends Model
{
    protected $table = 'product_characteristic_value';

    protected $fillable = [
        'product_id',
        'characteristic_id',
        'characteristic_value_id',
        'position',
        'source',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'characteristic_id' => 'integer',
        'characteristic_value_id' => 'integer',
        'position' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function characteristic(): BelongsTo
    {
        return $this->belongsTo(CharacteristicsProduct::class, 'characteristic_id');
    }

    public function value(): BelongsTo
    {
        return $this->belongsTo(CharacteristicValue::class, 'characteristic_value_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            // ✅ автопорядок у межах (product_id + characteristic_id)
            if ($m->position === null || (int) $m->position === 0) {
                $max = static::query()
                    ->where('product_id', $m->product_id)
                    ->where('characteristic_id', $m->characteristic_id)
                    ->max('position');

                $m->position = ((int) $max) + 1;
            }
        });
    }
}