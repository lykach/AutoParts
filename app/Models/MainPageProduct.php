<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MainPageProduct extends Model
{
    protected $fillable = [
        'group_id',
        'product_id',
        'sort',
    ];

    protected $casts = [
        'group_id' => 'integer',
        'product_id' => 'integer',
        'sort' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(MainPageGroup::class, 'group_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}