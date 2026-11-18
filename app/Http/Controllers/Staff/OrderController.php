<?php

namespace App\Http\Controllers\Staff;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\OrderTransitionException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    /** Daily work queue: today's and upcoming open orders, grouped by status. */
    public function index(Request $request): View
    {
        $status = OrderStatus::tryFrom((string) $request->query('estado'));

        $orders = Order::withCount('items')
            ->when($status, fn ($q) => $q->where('status', $status), fn ($q) => $q->whereNotIn('status', [OrderStatus::Delivered, OrderStatus::Cancelled]))
            ->orderBy('slot_date')
            ->orderBy('slot')
            ->paginate(30)
            ->withQueryString();

        $counts = Order::selectRaw('status, count(*) as how_many')->groupBy('status')->pluck('how_many', 'status');

        return view('staff.orders.index', compact('orders', 'status', 'counts'));
    }

    public function show(Order $order): View
    {
        return view('staff.orders.show', ['order' => $order->load(['items', 'user', 'creator', 'payment', 'statusChanges.user'])]);
    }

    public function advance(Request $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:255'],
            'weights' => ['array'],
            'weights.*' => ['nullable', 'numeric', 'gt:0', 'max:9999'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
        ], [], ['weights.*' => 'peso real', 'payment_method' => 'método de pago']);

        // Only weighted lines of this order may get a real weight.
        $weights = collect($data['weights'] ?? [])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->only($order->items()->where('unit_type', 'weight')->pluck('id')->all())
            ->map(fn ($value) => (float) $value)
            ->all();

        try {
            $order = $workflow->advance(
                $order, OrderStatus::from($data['status']), $request->user(), $data['note'] ?? null, $weights,
                PaymentMethod::tryFrom((string) ($data['payment_method'] ?? '')),
            );
        } catch (OrderTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Pedido {$order->code}: {$order->status->label()}.");
    }

    public function cancel(Request $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:255']], [], ['reason' => 'motivo']);

        try {
            $workflow->cancel($order, $request->user(), $data['reason']);
        } catch (OrderTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Pedido {$order->code} cancelado. El stock reservado vuelve a estar disponible.");
    }
}
