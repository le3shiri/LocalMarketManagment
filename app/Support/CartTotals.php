<?php

namespace App\Support;

use App\Enums\Fulfillment;
use App\Enums\UnitType;
use App\Models\CartItem;
use Illuminate\Support\Collection;

/**
 * Immutable price breakdown for a set of cart lines.
 */
final class CartTotals
{
    private function __construct(
        public readonly float $subtotal,
        public readonly float $deliveryFee,
        public readonly float $total,
        public readonly bool $hasWeightedItems,
        public readonly float $missingForFreeDelivery,
    ) {}

    /** @param  Collection<int, CartItem>  $items */
    public static function for(Collection $items, Fulfillment $fulfillment = Fulfillment::Pickup): self
    {
        $subtotal = round($items->sum(fn (CartItem $item) => $item->line_total), 2);
        $freeFrom = (float) config('shop.free_delivery_from');

        $fee = match (true) {
            $fulfillment === Fulfillment::Pickup, $items->isEmpty() => 0.0,
            $subtotal >= $freeFrom => 0.0,
            default => (float) config('shop.delivery_fee'),
        };

        return new self(
            subtotal: $subtotal,
            deliveryFee: $fee,
            total: round($subtotal + $fee, 2),
            hasWeightedItems: $items->contains(fn (CartItem $item) => $item->product->unit_type === UnitType::Weight),
            missingForFreeDelivery: max(0, round($freeFrom - $subtotal, 2)),
        );
    }
}
