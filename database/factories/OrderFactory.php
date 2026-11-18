<?php

namespace Database\Factories;

use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'channel' => 'web',
            'status' => OrderStatus::Pending,
            'fulfillment' => Fulfillment::Pickup,
            'contact_name' => fake()->name(),
            'contact_phone' => fake()->numerify('6## ### ###'),
            'slot_date' => today(),
            'slot' => '17:00-18:00',
            'subtotal' => 10,
            'delivery_fee' => 0,
            'total' => 10,
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
