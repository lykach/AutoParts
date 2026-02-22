<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ProductRelated extends Model
{
    protected $table = 'product_related';

    protected $fillable = [
        'product_id',
        'related_product_id',
        'sort_order',
        'is_active',
        'note',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'related_product_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function relatedProduct()
    {
        return $this->belongsTo(Product::class, 'related_product_id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $r) {
            if ((int) $r->product_id === (int) $r->related_product_id) {
                throw ValidationException::withMessages([
                    'related_product_id' => 'Супутній товар не може бути тим самим товаром.',
                ]);
            }

            $r->note = $r->note ? trim((string) $r->note) : null;

            if ($r->is_active === null) {
                $r->is_active = true;
            }

            // ✅ sort_order: якщо не задано — ставимо в кінець (max+1)
            if ($r->sort_order === null) {
                $max = (int) (static::query()
                    ->where('product_id', $r->product_id)
                    ->max('sort_order') ?? 0);

                $r->sort_order = $max + 1;
            }
        });
    }
}
