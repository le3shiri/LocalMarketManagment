<?php

namespace App\Enums;

/**
 * How a product is sold. Weighted products are ordered by approximate weight and the
 * final amount is set when the product is weighed in store (proposal, section 6).
 */
enum UnitType: string
{
    case Unit = 'unit';
    case Weight = 'weight';

    public function label(): string
    {
        return match ($this) {
            self::Unit => 'Por unidad',
            self::Weight => 'Por peso aproximado',
        };
    }

    public function suffix(): string
    {
        return match ($this) {
            self::Unit => 'ud',
            self::Weight => 'kg',
        };
    }

    /** Smallest quantity a customer can order. */
    public function step(): float
    {
        return match ($this) {
            self::Unit => 1,
            self::Weight => 0.25,
        };
    }
}
