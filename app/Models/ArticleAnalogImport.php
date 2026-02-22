<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleAnalogImport extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'is_active',
        'status',
        'inserted',
        'skipped',
        'disk',
        'path',
        'file_name',
        'error',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'inserted' => 'integer',
        'skipped' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
