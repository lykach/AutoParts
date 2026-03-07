<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductComponent extends Model
{
    protected $table = 'product_components';

    protected $fillable = [
        'product_id',
        'position',
        'title_uk',
        'title_en',
        'title_ru',
        'article_raw',
        'article_norm',
        'qty',
        'note',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'position' => 'integer',
        'qty' => 'decimal:3',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $c) {
            // title trim
            $c->title_uk = trim((string) $c->title_uk);
            $c->title_en = filled($c->title_en) ? trim((string) $c->title_en) : null;
            $c->title_ru = filled($c->title_ru) ? trim((string) $c->title_ru) : null;

            // якщо title_en / title_ru пусті рядки — в null
            if ($c->title_en === '') {
                $c->title_en = null;
            }

            if ($c->title_ru === '') {
                $c->title_ru = null;
            }

            // ✅ article normalize + UPPERCASE
            $raw = trim((string) ($c->article_raw ?? ''));

            if ($raw === '') {
                $c->article_raw = null;
                $c->article_norm = null;
            } else {
                $raw = mb_strtoupper($raw, 'UTF-8');
                $c->article_raw = $raw;

                if (empty($c->article_norm) || $c->isDirty('article_raw')) {
                    $c->article_norm = Product::normalizeArticle($raw);
                }
            }

            // qty safety
            if ($c->qty === null || (float) $c->qty <= 0) {
                $c->qty = 1;
            }

            // ✅ position safety: якщо не задано / 0 — ставимо "в кінець"
            if (empty($c->position) || (int) $c->position <= 0) {
                $max = (int) static::query()
                    ->where('product_id', $c->product_id)
                    ->where('id', '!=', $c->id ?? 0)
                    ->max('position');

                $c->position = $max + 1;
            }
        });
    }
}