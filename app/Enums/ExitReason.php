<?php

namespace App\Enums;

/** Why goods leave the stock other than by being sold. */
enum ExitReason: string
{
    case SupplierReturn = 'supplier_return';
    case InternalUse = 'internal_use';
    case Donation = 'donation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SupplierReturn => 'Devolución a proveedor',
            self::InternalUse => 'Consumo interno',
            self::Donation => 'Donación',
            self::Other => 'Otro motivo',
        };
    }
}
