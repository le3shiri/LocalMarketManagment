<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function show(Request $request, Sale $sale): View
    {
        return view('pos.ticket', [
            'sale' => $sale->load(['items', 'payment', 'cashier', 'refunds']),
            'justSold' => $request->boolean('nueva'),
        ]);
    }
}
