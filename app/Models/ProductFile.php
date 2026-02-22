<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductFile extends Model
{
    protected $table = 'product_files';

    protected $fillable = [
        'product_id',
        'type',
        'source',
        'title',
        'file_path',
        'external_url',
        'original_name',
        'mime',
        'size_bytes',
        'sort_order',
        'is_primary',
        'is_active',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'size_bytes' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute(): ?string
    {
        if ($this->external_url) {
            return $this->external_url;
        }

        return $this->file_path
            ? asset('storage/' . ltrim($this->file_path, '/'))
            : null;
    }

    protected static function booted(): void
    {
        static::saving(function (self $f) {

            $f->title = filled($f->title) ? trim($f->title) : null;
            $f->type = filled($f->type) ? strtolower(trim($f->type)) : null;
            $f->source = filled($f->source) ? strtolower(trim($f->source)) : 'upload';

            if ($f->is_active === null) {
                $f->is_active = true;
            }

            $hasFile = filled($f->file_path);
            $hasUrl  = filled($f->external_url);

            if (! $hasFile && ! $hasUrl) {
                throw ValidationException::withMessages([
                    'file_path' => 'Потрібно завантажити файл або вказати URL.',
                ]);
            }

            if ($hasUrl) {
                $f->source = 'url';
                $f->file_path = null;
            } else {
                $f->source = 'upload';
                $f->external_url = null;
            }

            // --- AUTO SORT ---
            if (empty($f->sort_order) || $f->sort_order <= 0) {
                $max = static::where('product_id', $f->product_id)
                    ->where('id', '!=', $f->id ?? 0)
                    ->max('sort_order') ?? 0;

                $f->sort_order = $max + 1;
            }

            // --- AUTO TYPE ---
            if (empty($f->type)) {
                $f->type = self::detectType($f);
            }

            // --- PRIMARY LOGIC ---
            if ($f->is_primary === null) {
                $f->is_primary = false;
            }

            if (! $f->is_active) {
                $f->is_primary = false;
            }

            if ($f->is_primary) {
                static::where('product_id', $f->product_id)
                    ->where('id', '!=', $f->id ?? 0)
                    ->update(['is_primary' => false]);
            }
        });

        static::saved(fn ($f) => self::stabilize($f->product_id));
        static::deleted(fn ($f) => self::stabilize($f->product_id));
    }

    public static function stabilize(int $productId): void
    {
        DB::transaction(function () use ($productId) {

            // нормалізуємо порядок 1..N
            $files = static::where('product_id', $productId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $i = 1;
            foreach ($files as $file) {
                if ($file->sort_order != $i) {
                    $file->updateQuietly(['sort_order' => $i]);
                }
                $i++;
            }

            // гарантуємо primary
            $hasPrimary = static::where('product_id', $productId)
                ->where('is_active', true)
                ->where('is_primary', true)
                ->exists();

            if (! $hasPrimary) {
                $first = static::where('product_id', $productId)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->first();

                if ($first) {
                    static::where('product_id', $productId)
                        ->update(['is_primary' => false]);

                    $first->updateQuietly(['is_primary' => true]);
                }
            }
        });
    }

    private static function detectType(self $f): string
    {
        $ext = strtolower(pathinfo(
            parse_url($f->external_url ?: $f->file_path, PHP_URL_PATH) ?? '',
            PATHINFO_EXTENSION
        ));

        if (in_array($ext, ['jpg','jpeg','png','webp','gif','svg'])) return 'photo';
        if (in_array($ext, ['pdf','doc','docx','txt'])) return 'manual';
        if (in_array($ext, ['dwg','dxf'])) return 'scheme';

        return 'other';
    }
}
