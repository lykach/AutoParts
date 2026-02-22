<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturerSynonym extends Model
{
    protected $fillable = [
        'manufacturer_id',
        'synonym',
    ];

    protected $casts = [
        'manufacturer_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $syn) {
            $syn->synonym = mb_strtoupper(trim((string) $syn->synonym), 'UTF-8');
        });
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
    }
}
