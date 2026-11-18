<?php

namespace App\Enums;

enum MovementType: string
{
    case Entry = 'entry';
    case Reserve = 'reserve';
    case Release = 'release';

    public function label(): string
    {
        return match ($this) {
            self::Entry => 'Entrada de mercancía',
            self::Reserve => 'Reserva de pedido',
            self::Release => 'Liberación de reserva',
        };
    }
}
