<?php

namespace App\Enums;

enum MovementType: string
{
    case Entry = 'entry';
    case Reserve = 'reserve';
    case Release = 'release';
    case Delivery = 'delivery';
    case Sale = 'sale';

    public function label(): string
    {
        return match ($this) {
            self::Entry => 'Entrada de mercancía',
            self::Reserve => 'Reserva de pedido',
            self::Release => 'Liberación de reserva',
            self::Delivery => 'Entrega de pedido',
            self::Sale => 'Venta en tienda',
        };
    }
}
