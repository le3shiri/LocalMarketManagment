<?php

namespace App\Http\Controllers\Warehouse;

use App\Enums\UnitType;
use App\Enums\WriteOffType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\WriteOff;
use App\Services\WriteOffService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Support\CsvExport;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Losses (mermas): record them and see what they cost by type and period.
 */
class WriteOffController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'type' => ['nullable', Rule::enum(WriteOffType::class)],
        ]);

        $from = CarbonImmutable::parse($filters['from'] ?? today()->startOfMonth())->startOfDay();
        $to = CarbonImmutable::parse($filters['to'] ?? today())->endOfDay();

        $query = WriteOff::whereBetween('created_at', [$from, $to])
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type));

        return view('warehouse.write-offs.index', [
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'byType' => (clone $query)->selectRaw('type, count(*) as records, sum(value) as value')->groupBy('type')->get()->keyBy(fn ($r) => $r->type->value),
            'total' => (float) (clone $query)->sum('value'),
            'writeOffs' => (clone $query)->with(['product', 'batch', 'user'])->latest('id')->paginate(25)->withQueryString(),
            'products' => Product::with(['stock', 'batches' => fn ($q) => $q->available()])->orderBy('name')->get(),
            'types' => WriteOffType::cases(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'type' => ['nullable', Rule::enum(WriteOffType::class)],
        ]);
        $from = CarbonImmutable::parse($filters['from'] ?? today()->startOfMonth())->startOfDay();
        $to = CarbonImmutable::parse($filters['to'] ?? today())->endOfDay();

        $query = WriteOff::with(['product', 'batch', 'user'])->whereBetween('created_at', [$from, $to])
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))->orderBy('id');

        return CsvExport::download('mermas',
            ['Fecha', 'Producto', 'Tipo', 'Origen', 'Lote', 'Cantidad', 'Valor', 'Observaciones', 'Responsable'],
            fn ($write) => $query->chunk(500, fn ($rows) => $rows->each(fn ($w) => $write([
                $w->created_at, $w->product->name, $w->type, $w->channel === 'online' ? 'Online' : 'Tienda', $w->batch?->lot_code,
                $w->quantity, CsvExport::money($w->value), $w->notes, $w->user?->name,
            ]))),
            "mermas {$from->format('d/m/Y')}–{$to->format('d/m/Y')}",
        );
    }

    public function store(Request $request, WriteOffService $writeOffs): RedirectResponse
    {
        $request->merge(['quantity' => str_replace(',', '.', (string) $request->input('quantity'))]);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'stock_batch_id' => ['nullable', 'exists:stock_batches,id'],
            'channel' => ['required', Rule::in(['store', 'online'])],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:99999'],
            'type' => ['required', Rule::enum(WriteOffType::class)],
            'notes' => ['nullable', 'string', 'max:200'],
        ], [], ['product_id' => 'producto', 'stock_batch_id' => 'lote', 'channel' => 'origen', 'quantity' => 'cantidad', 'type' => 'tipo de merma', 'notes' => 'observaciones']);

        $product = Product::findOrFail($data['product_id']);
        $quantity = (float) $data['quantity'];

        if ($product->unit_type === UnitType::Unit && fmod($quantity, 1) !== 0.0) {
            return back()->withInput()->withErrors(['quantity' => 'Este producto se mueve por unidades enteras.']);
        }

        try {
            $writeOffs->record($product, $quantity, $data['channel'], WriteOffType::from($data['type']), $request->user(),
                isset($data['stock_batch_id']) ? StockBatch::find($data['stock_batch_id']) : null, $data['notes'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['quantity' => $e->getMessage()]);
        }

        return redirect()->route('warehouse.write-offs.index')->with('status', "Merma registrada: {$product->name} (−{$product->formatQuantity($quantity)}).");
    }
}
