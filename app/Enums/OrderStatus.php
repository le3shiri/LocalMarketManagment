<?php

namespace App\Enums;

/** Order life cycle from section 6 of the proposal. */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Preparing => 'En preparación',
            self::Ready => 'Listo',
            self::Delivered => 'Entregado',
            self::Cancelled => 'Cancelado',
        };
    }

    /** The only forward step allowed from this status, if any. */
    public function next(): ?self
    {
        return match ($this) {
            self::Pending => self::Preparing,
            self::Preparing => self::Ready,
            self::Ready => self::Delivered,
            default => null,
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return $this->next() === $target;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled], true);
    }

    /** Label of the button that moves an order out of this status. */
    public function actionLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'Empezar a preparar',
            self::Preparing => 'Marcar como listo',
            self::Ready => 'Marcar como entregado',
            default => null,
        };
    }

    /** Tailwind classes for the status badge. */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'bg-warn-soft text-stock-low',
            self::Preparing => 'bg-action-soft text-action',
            self::Ready => 'bg-success-soft text-success-ink',
            self::Delivered => 'bg-sunken text-ink-soft',
            self::Cancelled => 'bg-red-50 text-red-800',
        };
    }
}
