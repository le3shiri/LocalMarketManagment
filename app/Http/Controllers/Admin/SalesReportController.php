<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Sale;
use App\Models\User;
use App\Support\CsvExport;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sales and payments for the owner: counter tickets plus orders paid on delivery.
 */
class SalesReportController extends Controller
{
    /** Till tickets with the same filters as the screen. */
    public function export(Request $request): StreamedResponse
    {
        [$from, $to, , $sales] = $this->salesQuery($request);

        return CsvExport::download('tickets',
            ['Ticket', 'Fecha', 'Cajero/a', 'Líneas', 'Método', 'Total', 'Entregado', 'Cambio', 'Devuelto'],
            fn ($write) => $sales->with(['payment', 'cashier'])->withCount('items')->withSum('refunds', 'amount')->orderBy('id')
                ->chunk(500, fn ($chunk) => $chunk->each(fn ($s) => $write([
                    $s->code, $s->created_at, $s->cashier?->name, $s->items_count, $s->payment?->method,
                    CsvExport::money($s->total), $s->payment?->tendered ? CsvExport::money($s->payment->tendered) : null,
                    $s->payment?->change ? CsvExport::money($s->payment->change) : null, CsvExport::money($s->refunds_sum_amount),
                ]))),
            "tickets de caja {$from->format('d/m/Y')}–{$to->format('d/m/Y')}",
        );
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: array, 3: \Illuminate\Database\Eloquent\Builder} */
    private function salesQuery(Request $request): array
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'cashier' => ['nullable', 'integer'],
            'method' => ['nullable', Rule::enum(PaymentMethod::class)],
        ]);

        // Default period: the last 7 days.
        $from = CarbonImmutable::parse($filters['from'] ?? today()->subDays(6))->startOfDay();
        $to = CarbonImmutable::parse($filters['to'] ?? today())->endOfDay();

        $sales = Sale::query()
            ->whereBetween('created_at', [$from, $to])
            ->when($filters['cashier'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['method'] ?? null, fn ($q, $method) => $q->whereHas('payment', fn ($q) => $q->where('method', $method)));

        return [$from, $to, $filters, $sales];
    }

    public function index(Request $request): View
    {
        [$from, $to, $filters, $sales] = $this->salesQuery($request);

        $payments = Payment::query()
            ->whereBetween('created_at', [$from, $to])
            ->when($filters['cashier'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['method'] ?? null, fn ($q, $method) => $q->where('method', $method));

        $ticketCount = (clone $sales)->count();
        $salesTotal = (float) (clone $sales)->sum('total');

        return view('admin.sales.index', [
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'ticketCount' => $ticketCount,
            'salesTotal' => $salesTotal,
            'averageTicket' => $ticketCount ? round($salesTotal / $ticketCount, 2) : 0,
            'byMethod' => (clone $payments)->selectRaw('method, payable_type, count(*) as operations, sum(amount) as amount')
                ->groupBy('method', 'payable_type')->get()
                ->groupBy(fn ($row) => $row->method->value),
            'byDay' => (clone $sales)->selectRaw('date(created_at) as day, count(*) as tickets, sum(total) as amount')
                ->groupBy('day')->orderByDesc('day')->get(),
            'tickets' => (clone $sales)->with(['payment', 'cashier'])->withCount('items')->latest('id')->paginate(20)->withQueryString(),
            'cashiers' => User::whereIn('id', Payment::query()->select('user_id')->whereNotNull('user_id')->distinct())->orderBy('name')->get(['id', 'name']),
            'sourceLabels' => [(new Sale)->getMorphClass() => 'Caja', (new Order)->getMorphClass() => 'Pedidos', (new Refund)->getMorphClass() => 'Devoluciones'],
            'refundsTotal' => (float) Refund::whereBetween('created_at', [$from, $to])
                ->when($filters['cashier'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
                ->sum('amount'),
        ]);
    }
}
