<?php

namespace App\Support;

use Illuminate\Support\Str;

class CharacteristicValueKey
{
    public static function fromText(?string $uk, ?string $en = null): string
    {
        $base = trim((string) ($uk ?: $en ?: 'value'));
        $slug = Str::slug($base);

        return $slug !== '' ? $slug : 'value';
    }

    public static function fromNumber($value, int $decimals = 0): ?string
    {
        if ($value === null || $value === '') return null;

        $normalized = str_replace(',', '.', trim((string) $value));
        if ($normalized === '' || ! is_numeric($normalized)) return null;

        $decimals = max(0, min(6, $decimals)); // запобіжник

        if ($decimals === 0) {
            return (string) ((int) round((float) $normalized));
        }

        return number_format((float) $normalized, $decimals, '.', '');
    }

    public static function fromBool($value): ?string
    {
        if ($value === null || $value === '') return null;

        // підтримка: true/false, 1/0, так/ні, yes/no
        $s = mb_strtolower(trim((string) $value), 'UTF-8');

        if (in_array($s, ['1', 'true', 'так', 'yes', 'y', 'on'], true)) return '1';
        if (in_array($s, ['0', 'false', 'ні', 'no', 'n', 'off'], true)) return '0';

        // якщо прийшло bool
        if (is_bool($value)) return $value ? '1' : '0';

        return null;
    }
}