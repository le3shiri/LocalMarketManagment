<?php

namespace App\Http\Controllers\Warehouse;

use App\Enums\MovementType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\CsvExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The stock ledger: every entry, sale, reservation, delivery, return, exit and adjustment.
 */
class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('warehouse.movements.index', [
            'movements' => $this->query($filters)->with(['product', 'user'])->paginate(40)->withQueryString(),
            'filters' => $filters,
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku']),
            'users' => User::whereIn('id', StockMovement::query()->select('user_id')->whereNotNull('user_id')->distinct())->orderBy('name')->get(['id', 'name']),
            'types' => MovementType::cases(),
        ]);
    }

    /** Same filters as the screen, as a CSV the owner can open in a spreadsheet. */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->query($this->filters($request))->with(['product', 'user']);

        return CsvExport::download('movimientos-stock',
            ['Fecha', 'Producto', 'SKU', 'Tipo', 'Tienda', 'Online', 'Reservado', 'Existencias tras', 'Referencia', 'Motivo', 'Responsable'],
            fn ($write) => $query->chunk(500, fn ($movements) => $movements->each(fn ($m) => $write([
                $m->created_at, $m->product?->name, $m->product?->sku, $m->type,
                $m->store_delta, $m->online_delta, $m->reserved_delta, $m->on_hand_after,
                $m->reference, $m->reason, $m->user?->name ?? 'Sistema',
            ]))),
            'movimientos de stock',
        );
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'product' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::enum(MovementType::class)],
            'user' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
    }

    private function query(array $filters): Builder
    {
        return StockMovement::query()
            ->when($filters['product'] ?? null, fn ($q, $id) => $q->where('product_id', $id))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['user'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->latest('id');
    }
}
