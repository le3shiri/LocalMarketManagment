<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Models\Sale;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class RefundController extends Controller
{
    public function create(Sale $sale): View
    {
        return view('pos.refunds.create', [
            'sale' => $sale->load(['items.product', 'payment']),
            'deadline' => $sale->created_at->copy()->addDays((int) config('shop.refund_days'))->endOfDay(),
        ]);
    }

    public function store(Request $request, Sale $sale, RefundService $refunds): RedirectResponse
    {
        $data = $request->validate([
            'quantities' => ['required', 'array'],
            'quantities.*' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ], [], ['quantities.*' => 'cantidad', 'reason' => 'motivo']);

        try {
            $refund = $refunds->refund($sale, $data['quantities'], $data['reason'], $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('pos.refunds.show', $refund)->with('status', "Devolución {$refund->code} registrada.");
    }

    public function show(Refund $refund): View
    {
        return view('pos.refunds.show', ['refund' => $refund->load(['items.saleItem', 'sale', 'payment', 'user'])]);
    }
}
