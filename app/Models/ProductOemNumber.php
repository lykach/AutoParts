<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOemNumber extends Model
{
    protected $fillable = [
        'product_id',
        'oem_number_raw',
        'oem_number_norm',
        'manufacturer_id',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'manufacturer_id' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $n) {
            $raw = trim((string) ($n->oem_number_raw ?? ''));

            if ($raw === '') {
                $n->oem_number_raw = null;
                $n->oem_number_norm = null;
                return;
            }

            // ✅ завжди у верхній регістр (UTF-8)
            $raw = mb_strtoupper($raw, 'UTF-8');
            $n->oem_number_raw = $raw;

            // ✅ norm рахуємо як у Product::normalizeArticle (він теж робить upper)
            if (empty($n->oem_number_norm) || $n->isDirty('oem_number_raw')) {
                $n->oem_number_norm = Product::normalizeArticle($raw);
            }
        });
    }
}
