<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UkrainianPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $normalized = self::normalize((string) $value);

        if (! $normalized) {
            $fail('Телефон повинен бути у форматі: +380 XX XXX XX XX');
        }
    }

    /**
     * Normalize phone to +380XXXXXXXXX for storage (E.164 for UA)
     */
    public static function normalize(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        // 380XXXXXXXXX (12 digits)
        if (strlen($digits) === 12 && str_starts_with($digits, '380')) {
            return '+' . $digits;
        }

        // 0XXXXXXXXX (10 digits) -> +38 0XXXXXXXXX = +380XXXXXXXXX
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return '+38' . $digits;
        }

        // 38XXXXXXXXX (11 digits) -> +38XXXXXXXXX (але нам треба +380...)
        // Найчастіше це "38 0XXXXXXXXX" без плюса, тобто 380XXXXXXXXX
        if (strlen($digits) === 11 && str_starts_with($digits, '38')) {
            // Якщо це 380XXXXXXXXX без однієї цифри — не вгадуємо, відхиляємо
            // Але якщо це "38" + "0XXXXXXXXX" (тобто 38 + 10 цифр) -> додамо +
            return '+' . $digits;
        }

        // Якщо прилетіло щось інше — не нормалізуємо
        return null;
    }

    /**
     * Optional: human-friendly format for display
     */
    public static function format(?string $phone): ?string
    {
        $normalized = self::normalize($phone);
        if (! $normalized) {
            return $phone;
        }

        $numbers = preg_replace('/\D+/', '', $normalized) ?? '';

        // 380XXXXXXXXX -> +38 (0XX) XXX-XX-XX
        if (strlen($numbers) === 12 && str_starts_with($numbers, '380')) {
            return preg_replace(
                '/^380(\d{2})(\d{3})(\d{2})(\d{2})$/',
                '+38 (0$1) $2-$3-$4',
                $numbers
            );
        }

        return $normalized;
    }
}
