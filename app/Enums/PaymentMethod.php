<?php

namespace App\Enums;

/**
 * Payment methods available in the shop. Card payments go through the shop's card
 * terminal (datáfono): the app only records an optional authorisation code and never
 * stores card data. Online payment is planned for phase 3.
 */
enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Card => 'Tarjeta',
        };
    }
}
