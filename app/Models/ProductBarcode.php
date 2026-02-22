<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ProductBarcode extends Model
{
    protected $table = 'product_barcodes';

    protected $fillable = [
        'product_id',
        'barcode',
        'type',
        'is_primary',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'is_primary' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Нормалізація: лишаємо тільки цифри (EAN/UPC),
     * прибираємо пробіли/дефіси/інші символи.
     */
    public static function normalizeBarcode(?string $barcode): string
    {
        $s = trim((string) $barcode);
        if ($s === '') {
            return '';
        }

        // лишаємо тільки цифри
        $s = preg_replace('/\D+/', '', $s) ?? '';

        return $s;
    }

    public static function detectType(string $digits): ?string
    {
        $len = strlen($digits);

        return match ($len) {
            8  => 'EAN8',
            12 => 'UPCA',   // UPC-A (12)
            13 => 'EAN13',
            14 => 'GTIN14', // інколи трапляється
            default => null,
        };
    }

    protected static function booted(): void
    {
        static::saving(function (self $b) {
            $digits = self::normalizeBarcode($b->barcode);

            if ($digits === '') {
                throw ValidationException::withMessages([
                    'barcode' => 'Штрих-код не може бути порожнім.',
                ]);
            }

            // записуємо вже нормалізований
            $b->barcode = $digits;

            // якщо type не заданий або змінився barcode — визначимо
            if (empty($b->type) || $b->isDirty('barcode')) {
                $b->type = self::detectType($digits) ?? 'OTHER';
            }

            // якщо ставимо primary — інші primary для цього товару знімаємо
            if ($b->is_primary) {
                static::query()
                    ->where('product_id', $b->product_id)
                    ->where('id', '!=', $b->id ?? 0)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });
    }
}
