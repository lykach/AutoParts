<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MainPageGroup extends Model
{
    protected $fillable = [
        'caption',
        'sort',
        'show_caption',
        'is_active',
    ];

    protected $casts = [
        'sort' => 'integer',
        'show_caption' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MainPageProduct::class, 'group_id')
            ->orderBy('sort')
            ->orderBy('id');
    }
}