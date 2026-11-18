<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderTransitionException;
use App\Models\Order;
use App\Services\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /** "Mis pedidos": every order linked to the customer, whatever the channel. */
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()
            ->withCount('items')
            ->latest('id')
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return view('orders.show', ['order' => $order->load('items')]);
    }

    public function cancel(Request $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        try {
            $workflow->cancel($order, $request->user(), 'Cancelado por el cliente desde la web', byCustomer: true);
        } catch (OrderTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Has cancelado el pedido {$order->code}.");
    }
}
