<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderTransitionException;
use App\Models\Order;
use App\Services\CartService;
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

    public function repeat(Request $request, Order $order, CartService $cart): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $result = $cart->addFromOrder($request->user(), $order);

        if ($result['added'] === [] && $result['adjusted'] === []) {
            return back()->with('error', 'No se ha podido añadir nada: '.implode(', ', $result['skipped']).'.');
        }

        return redirect()->route('cart.index')
            ->with('status', "Hemos añadido a tu carrito los productos del pedido {$order->code} con los precios de hoy.")
            ->with('repeat', $result);
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
