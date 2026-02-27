<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockSource extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'is_active',
        'sort_order',

        'default_currency_code',

        // ✅ дефолтна доставка
        'delivery_unit', // days|hours
        'delivery_min',
        'delivery_max',

        'contact_name',
        'phone',
        'email',
        'website_url',

        'country',
        'city',
        'address_line1', // UI: "Вулиця"

        'settings',
        'note',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'default_currency_code' => 'string',

        'delivery_unit' => 'string',
        'delivery_min' => 'integer',
        'delivery_max' => 'integer',

        'settings' => 'array',
    ];

    public function storeLinks(): HasMany
    {
        return $this->hasMany(StoreStockSource::class, 'stock_source_id');
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_stock_sources', 'stock_source_id', 'store_id')
            ->withPivot([
                'is_active',
                'priority',
                'markup_percent',
                'min_delivery_days',
                'max_delivery_days',
                'lead_time_days',
                'cutoff_time',
                'pickup_available',
                'price_multiplier',
                'extra_fee',
                'min_order_amount',
                'coverage',
                'settings',
                'note',
            ])
            ->withTimestamps();
    }

    public function locations(): HasMany
    {
        return $this->hasMany(StockSourceLocation::class, 'stock_source_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockItem::class, 'stock_source_id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $s) {
            $s->website_url = $s->website_url ? trim($s->website_url) : null;

            if (!filled($s->name)) {
                throw ValidationException::withMessages(['name' => 'Назва джерела обов’язкова.']);
            }

            if (!filled($s->code)) {
                $s->code = Str::upper(Str::slug($s->name ?: 'source', '_'));
            } else {
                $s->code = Str::upper(trim((string) $s->code));
            }

            $s->phone = $s->phone ? trim((string) $s->phone) : null;
            $s->email = $s->email ? trim((string) $s->email) : null;

            $s->type = trim((string) $s->type);

            $s->default_currency_code = $s->default_currency_code
                ? Str::upper(trim((string) $s->default_currency_code))
                : 'UAH';

            // ✅ доставка дефолт: days|hours
            $unit = strtolower(trim((string) ($s->delivery_unit ?? 'days')));
            $s->delivery_unit = in_array($unit, ['days', 'hours'], true) ? $unit : 'days';

            $s->delivery_min = filled($s->delivery_min) ? (int) $s->delivery_min : null;
            $s->delivery_max = filled($s->delivery_max) ? (int) $s->delivery_max : null;

            if ($s->delivery_min !== null && $s->delivery_min < 0) $s->delivery_min = 0;
            if ($s->delivery_max !== null && $s->delivery_max < 0) $s->delivery_max = 0;

            if ($s->delivery_min !== null && $s->delivery_max !== null && $s->delivery_min > $s->delivery_max) {
                [$s->delivery_min, $s->delivery_max] = [$s->delivery_max, $s->delivery_min];
            }

            if ($s->sort_order === null) {
                if ($s->exists) {
                    $s->sort_order = (int) ($s->getOriginal('sort_order') ?? 100);
                } else {
                    $max = self::query()->max('sort_order');
                    $s->sort_order = (int) (($max ?? 0) + 100);
                }
            }
        });

        static::deleting(function (self $s) {
            if ($s->storeLinks()->exists()) {
                throw ValidationException::withMessages([
                    'stock_source' => 'Неможливо видалити: джерело використовується в магазинах (є привʼязки).',
                ]);
            }

            if ($s->locations()->exists()) {
                throw ValidationException::withMessages([
                    'stock_source' => 'Неможливо видалити: у джерела є склади/локації.',
                ]);
            }

            if ($s->items()->exists()) {
                throw ValidationException::withMessages([
                    'stock_source' => 'Неможливо видалити: у джерелі є залишки товарів (stock_items).',
                ]);
            }
        });
    }

    public static function typeOptions(): array
    {
        return [
            'own_warehouse'     => 'Власний склад',
            'branch_warehouse'  => 'Склад філії',
            'supplier_price'    => 'Постачальник (прайс)',
            'supplier_api'      => 'Постачальник (API)',
            'manual'            => 'Ручне джерело',
            'dropship'          => 'Dropship',
            'other'             => 'Інше',
        ];
    }

    public static function deliveryUnitOptions(): array
    {
        return [
            'days' => 'Дні',
            'hours' => 'Години',
        ];
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