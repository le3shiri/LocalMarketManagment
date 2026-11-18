<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * EAN-13 barcode: 13 digits whose last digit is the GS1 check digit.
 */
class Ean13 implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isValid((string) $value)) {
            $fail('El :attribute no es un código EAN-13 válido.');
        }
    }

    public static function isValid(string $code): bool
    {
        if (! preg_match('/^\d{13}$/', $code)) {
            return false;
        }

        return self::checkDigit(substr($code, 0, 12)) === (int) $code[12];
    }

    /** GS1 check digit for the first 12 digits: weights 1,3,1,3… from the left. */
    public static function checkDigit(string $twelveDigits): int
    {
        $sum = 0;
        foreach (str_split($twelveDigits) as $i => $digit) {
            $sum += (int) $digit * ($i % 2 === 0 ? 1 : 3);
        }

        return (10 - $sum % 10) % 10;
    }
}
