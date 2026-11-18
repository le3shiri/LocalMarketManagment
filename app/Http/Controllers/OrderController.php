<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        // Customers only see their own orders; order history and staff views come in Sprint 3.
        abort_unless($order->user_id === $request->user()->id, 404);

        return view('orders.show', ['order' => $order->load('items')]);
    }
}
