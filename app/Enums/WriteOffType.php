<?php

namespace App\Enums;

/** Kinds of loss (merma) recorded by the warehouse. */
enum WriteOffType: string
{
    case Damaged = 'damaged';
    case Expired = 'expired';
    case Recalled = 'recalled';

    public function label(): string
    {
        return match ($this) {
            self::Damaged => 'Dañado / roto',
            self::Expired => 'Caducado',
            self::Recalled => 'Retirado (sanidad / proveedor)',
        };
    }
}
