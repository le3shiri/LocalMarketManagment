<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * All orders from every channel for the owner: filters, totals per channel and
 * a "per day and status" view (section 6: "la propietaria puede consultar los pedidos
 * por día y por estado").
 */
class OrderSupervisionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'channel' => ['nullable', Rule::in(OrderService::CHANNELS)],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'fulfillment' => ['nullable', Rule::enum(Fulfillment::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = $this->filtered($filters);

        $orders = (clone $query)->with('user')->latest('id')->paginate(25)->withQueryString();

        // Sales figures exclude cancelled orders.
        $byChannel = (clone $query)
            ->where('status', '!=', OrderStatus::Cancelled)
            ->selectRaw('channel, count(*) as orders, sum(total) as amount')
            ->groupBy('channel')
            ->get()
            ->keyBy('channel');

        $byDay = (clone $query)
            ->selectRaw('slot_date, status, count(*) as orders')
            ->groupBy('slot_date', 'status')
            ->orderByDesc('slot_date')
            ->get()
            ->groupBy(fn ($row) => substr((string) $row->slot_date, 0, 10))
            ->take(14);

        return view('admin.orders.index', [
            'orders' => $orders,
            'byChannel' => $byChannel,
            'byDay' => $byDay,
            'filters' => $filters,
            'channels' => ['web' => 'Web', 'phone' => 'Teléfono', 'whatsapp' => 'WhatsApp'],
        ]);
    }

    private function filtered(array $filters): Builder
    {
        return Order::query()
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('code', 'like', "%{$term}%")
                ->orWhere('contact_name', 'like', "%{$term}%")
                ->orWhere('contact_phone', 'like', "%{$term}%")))
            ->when($filters['channel'] ?? null, fn ($q, $channel) => $q->where('channel', $channel))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['fulfillment'] ?? null, fn ($q, $value) => $q->where('fulfillment', $value))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('slot_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('slot_date', '<=', $to));
    }
}
