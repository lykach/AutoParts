<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleAnalogExport extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'only_active',
        'status',
        'rows',
        'disk',
        'path',
        'file_name',
        'error',
    ];

    protected $casts = [
        'only_active' => 'boolean',
        'rows' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
