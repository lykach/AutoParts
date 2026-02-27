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
        'city',
        'address_line1', // UI: "Вулиця"

        // ✅ доставка (може бути null => успадкувати з джерела)
        'delivery_unit',
        'delivery_min',
        'delivery_max',

        'settings',
        'note',
    ];

    protected $casts = [
        'stock_source_id' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',

        'delivery_unit' => 'string',
        'delivery_min' => 'integer',
        'delivery_max' => 'integer',

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

            // ✅ доставка: null => inherit
            $l->delivery_min = filled($l->delivery_min) ? (int) $l->delivery_min : null;
            $l->delivery_max = filled($l->delivery_max) ? (int) $l->delivery_max : null;

            if (filled($l->delivery_unit)) {
                $unit = strtolower(trim((string) $l->delivery_unit));
                $l->delivery_unit = in_array($unit, ['days', 'hours'], true) ? $unit : null;
            } else {
                $l->delivery_unit = null;
            }

            if ($l->delivery_min !== null && $l->delivery_min < 0) $l->delivery_min = 0;
            if ($l->delivery_max !== null && $l->delivery_max < 0) $l->delivery_max = 0;

            if ($l->delivery_min !== null && $l->delivery_max !== null && $l->delivery_min > $l->delivery_max) {
                [$l->delivery_min, $l->delivery_max] = [$l->delivery_max, $l->delivery_min];
            }

            // ✅ якщо юзер не задав — беремо з джерела
            if ($l->delivery_unit === null || $l->delivery_min === null && $l->delivery_max === null) {
                $src = StockSource::query()
                    ->select(['id', 'delivery_unit', 'delivery_min', 'delivery_max'])
                    ->whereKey($l->stock_source_id)
                    ->first();

                if ($l->delivery_unit === null) {
                    $l->delivery_unit = $src?->delivery_unit ?: null; // можна лишити null, але краще наслідувати
                }

                if ($l->delivery_min === null && $l->delivery_max === null) {
                    $l->delivery_min = $src?->delivery_min;
                    $l->delivery_max = $src?->delivery_max;
                }
            }

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

    public function formatDelivery(): string
    {
        $u = $this->delivery_unit ?: 'days';
        $suffix = $u === 'hours' ? 'год.' : 'дн.';

        $min = $this->delivery_min;
        $max = $this->delivery_max;

        if ($min === null && $max === null) return '—';
        if ($min !== null && $max !== null) return "{$min}-{$max} {$suffix}";
        if ($min !== null) return "від {$min} {$suffix}";
        return "до {$max} {$suffix}";
    }
}