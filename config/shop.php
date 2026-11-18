<?php

return [
    /*
    | Delivery pricing. Pickup in store is always free.
    */
    'delivery_fee' => (float) env('SHOP_DELIVERY_FEE', 3.50),
    'free_delivery_from' => (float) env('SHOP_FREE_DELIVERY_FROM', 50),

    /*
    | Pickup / delivery slots offered at checkout (proposal, section 6).
    */
    'slots' => [
        '10:00-11:00', '11:00-12:00', '12:00-13:00', '17:00-18:00', '18:00-19:00', '19:00-20:00',
    ],
    'max_orders_per_slot' => (int) env('SHOP_MAX_ORDERS_PER_SLOT', 8),
    'slot_days_ahead' => 3,

    /*
    | Returns: maximum days after the sale. Perishable products are refunded but never
    | go back to the shelf.
    */
    'refund_days' => (int) env('SHOP_REFUND_DAYS', 15),
];
