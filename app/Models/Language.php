<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'name_uk',
        'name_en',
        'name_ru',
        'code',
        'is_default',
        'is_active',
        'lng_id',
        'lng_codepage',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'lng_id' => 'integer',
        'lng_codepage' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function (self $language) {
            if ($language->is_default) {
                // ✅ Головна мова завжди активна
                $language->is_active = true;

                // ✅ Знімаємо default з інших мов без зайвих сайд-ефектів
                static::withoutEvents(function () use ($language) {
                    static::query()
                        ->where('id', '!=', $language->id)
                        ->where('is_default', true)
                        ->update(['is_default' => false]);
                });
            }
        });

        static::deleting(function (self $language) {
            if ($language->is_default) {
                throw new \Exception('Неможливо видалити головну мову сайту!');
            }
        });
    }

    /**
     * Отримати локалізовану назву мови
     */
    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        $columnName = "name_{$locale}";

        if (!empty($this->{$columnName})) {
            return $this->{$columnName};
        }

        return $this->name_uk ?? $this->code ?? (string) $this->id;
    }
}
