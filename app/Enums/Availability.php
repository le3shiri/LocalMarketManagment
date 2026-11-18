<?php

namespace App\Enums;

enum Availability: string
{
    case Available = 'available';
    case Low = 'low';
    case SoldOut = 'sold_out';

    /** Online units (or kg) at or below which a product shows "last units". */
    public const LOW_THRESHOLD = 3;

    public static function fromQuantity(float $availableOnline): self
    {
        return match (true) {
            $availableOnline <= 0 => self::SoldOut,
            $availableOnline <= self::LOW_THRESHOLD => self::Low,
            default => self::Available,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Low => 'Últimas unidades',
            self::SoldOut => 'Agotado',
        };
    }

    public function canBuy(): bool
    {
        return $this !== self::SoldOut;
    }
}
