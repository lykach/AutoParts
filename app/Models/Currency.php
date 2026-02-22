<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'iso_code',
        'symbol',
        'short_name_uk',
        'short_name_en',
        'short_name_ru',
        'rate',
        'is_default',
        'is_active',
        'rate_updated_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'rate' => 'decimal:4',
        'rate_updated_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function (self $currency) {
            if ($currency->is_default) {
                // ✅ Головна валюта завжди активна і має курс 1.0000
                $currency->rate = 1.0000;
                $currency->is_active = true;

                // ✅ Знімаємо default з інших валют без зайвих сайд-ефектів
                static::withoutEvents(function () use ($currency) {
                    static::query()
                        ->where('id', '!=', $currency->id)
                        ->where('is_default', true)
                        ->update(['is_default' => false]);
                });
            }
        });

        static::deleting(function (self $currency) {
            if ($currency->is_default) {
                throw new \Exception('Неможливо видалити головну валюту магазину!');
            }
        });
    }

    /**
     * Отримати локалізоване коротке ім'я валюти
     */
    public function getLocalizedShortNameAttribute(): string
    {
        $locale = app()->getLocale();
        $columnName = "short_name_{$locale}";

        if (!empty($this->{$columnName})) {
            return $this->{$columnName};
        }

        return $this->short_name_uk ?? $this->code ?? (string) $this->id;
    }
}
