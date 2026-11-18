<?php

namespace App\Enums;

enum Fulfillment: string
{
    case Pickup = 'pickup';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Recogida en tienda',
            self::Delivery => 'Envío a domicilio',
        };
    }
}
