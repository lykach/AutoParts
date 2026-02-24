<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockSourceLocation extends Model
{
    protected $table = 'stock_source_locations';

    protected $fillable = [
        'stock_source_id',
        'code',
        'name',
        'is_active',
        'sort_order',

        'country',
        'region',
        'city',
        'address_line1',
        'address_line2',
        'postal_code',
        'lat',
        'lng',

        'settings',
        'note',
    ];

    protected $casts = [
        'stock_source_id' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'settings' => 'array',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(StockSource::class, 'stock_source_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockItem::class, 'stock_source_location_id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $l) {
            if (!filled($l->stock_source_id)) {
                throw ValidationException::withMessages([
                    'stock_source_id' => 'Потрібно вибрати джерело/постачальника.',
                ]);
            }

            if (!filled($l->name)) {
                throw ValidationException::withMessages([
                    'name' => 'Назва складу обов’язкова.',
                ]);
            }

            $l->code = filled($l->code)
                ? Str::upper(trim((string) $l->code))
                : Str::upper(Str::slug($l->name ?: 'location', '_'));

            /**
             * ✅ Гарантуємо sort_order:
             * - якщо юзер стер значення ("" -> null) при редагуванні — повертаємо старе
             * - якщо це новий запис і sort_order null — ставимо max+100
             */
            if ($l->sort_order === null) {
                if ($l->exists) {
                    $l->sort_order = (int) ($l->getOriginal('sort_order') ?? 100);
                } else {
                    $max = self::query()
                        ->where('stock_source_id', $l->stock_source_id)
                        ->max('sort_order');

                    $l->sort_order = (int) (($max ?? 0) + 100);
                }
            }
        });

        static::deleting(function (self $l) {
            if ($l->items()->exists()) {
                throw ValidationException::withMessages([
                    'location' => 'Неможливо видалити: у складі є позиції (stock_items).',
                ]);
            }
        });
    }
}