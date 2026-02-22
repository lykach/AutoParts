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

        'min_order_default_qty',

        // ✅ NEW
        'default_currency_code',

        'contact_name',
        'phone',
        'email',
        'website_url',

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
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'min_order_default_qty' => 'integer',

        // ✅ NEW
        'default_currency_code' => 'string',

        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
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

            if ($s->sort_order === null) {
                $s->sort_order = 100;
            }

            // ✅ валюта за замовчуванням
            $s->default_currency_code = $s->default_currency_code
                ? Str::upper(trim((string) $s->default_currency_code))
                : 'UAH';
        });

        static::deleting(function (self $s) {
            if ($s->storeLinks()->exists()) {
                throw ValidationException::withMessages([
                    'stock_source' => 'Неможливо видалити: джерело використовується в магазинах (є привʼязки).',
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
}
