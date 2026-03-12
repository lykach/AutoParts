<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryMethod extends Model
{
    use SoftDeletes;

    protected $table = 'delivery_methods';

    protected $fillable = [
        'code',
        'name_uk',
        'name_en',
        'name_ru',
        'description_uk',
        'description_en',
        'description_ru',
        'type',
        'is_active',
        'sort_order',
        'icon',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'settings' => 'array',
    ];

    public function storeLinks(): HasMany
    {
        return $this->hasMany(StoreDeliveryMethod::class, 'delivery_method_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::saving(function (self $row) {
            $row->code = trim((string) $row->code);
            $row->name_uk = trim((string) $row->name_uk);

            $row->name_en = filled($row->name_en) ? trim((string) $row->name_en) : null;
            $row->name_ru = filled($row->name_ru) ? trim((string) $row->name_ru) : null;

            $row->description_uk = filled($row->description_uk) ? trim((string) $row->description_uk) : null;
            $row->description_en = filled($row->description_en) ? trim((string) $row->description_en) : null;
            $row->description_ru = filled($row->description_ru) ? trim((string) $row->description_ru) : null;

            $row->type = trim((string) $row->type);
            $row->sort_order = filled($row->sort_order) ? (int) $row->sort_order : 100;
            $row->settings = is_array($row->settings) ? $row->settings : [];
            $row->icon = filled($row->icon) ? trim((string) $row->icon) : null;
        });
    }
}