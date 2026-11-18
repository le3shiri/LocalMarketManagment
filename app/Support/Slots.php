<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Pickup / delivery time slots, each with a maximum number of orders so the shop is not
 * overloaded at rush hour (proposal, section 6).
 */
class Slots
{
    /** @return Collection<int, CarbonImmutable> */
    public function days(): Collection
    {
        $today = CarbonImmutable::today();

        return collect(range(0, (int) config('shop.slot_days_ahead')))
            ->map(fn (int $offset) => $today->addDays($offset))
            ->reject(fn (CarbonImmutable $day) => $day->isSunday())
            ->values();
    }

    /**
     * Slots for a day with the remaining capacity; past slots of today are excluded.
     *
     * @return array<string, int> slot => free places
     */
    public function available(CarbonImmutable $day): array
    {
        $max = (int) config('shop.max_orders_per_slot');

        $taken = Order::whereDate('slot_date', $day)
            ->where('status', '!=', OrderStatus::Cancelled)
            ->selectRaw('slot, count(*) as how_many')
            ->groupBy('slot')
            ->pluck('how_many', 'slot');

        return collect(config('shop.slots'))
            ->reject(fn (string $slot) => $day->isToday() && $day->setTimeFromTimeString(substr($slot, 0, 5))->isPast())
            ->mapWithKeys(fn (string $slot) => [$slot => max(0, $max - (int) ($taken[$slot] ?? 0))])
            ->all();
    }

    public function hasRoom(CarbonImmutable $day, string $slot): bool
    {
        return ($this->available($day)[$slot] ?? 0) > 0;
    }
}
