<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductTranslation extends Model
{
    protected $fillable = [
        'product_id',
        'locale',
        'name',
        'slug',
        'short_description',
        'description',
        'meta_title',
        'meta_description',
        'source',
        'is_locked',
        'updated_by',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'is_locked' => 'boolean',
        'updated_by' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $t) {
            $t->name = $t->name ? trim((string) $t->name) : null;
            $t->slug = $t->slug ? trim((string) $t->slug) : null;

            // fallback (якщо десь створюють translation напряму)
            if (empty($t->slug) && !empty($t->name)) {
                $t->slug = Str::slug($t->name);
            }
        });
    }
}
