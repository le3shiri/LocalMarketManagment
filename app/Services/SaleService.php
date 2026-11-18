<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\AuditLog;
use App\Models\Sale;
use App\Models\User;
use App\Support\SaleTotals;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Completes counter sales: ticket, lines, payment and stock in a single transaction.
 */
class SaleService
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * @param  array{method: PaymentMethod, tendered: ?float, change: ?float, reference: ?string}  $payment
     *
     * @throws InvalidArgumentException when the sale is empty or stock doesn't cover it
     */
    public function complete(SaleTotals $totals, array $payment, User $cashier): Sale
    {
        if ($totals->isEmpty()) {
            throw new InvalidArgumentException('La venta no tiene productos.');
        }

        return DB::transaction(function () use ($totals, $payment, $cashier) {
            $sale = Sale::create([
                'user_id' => $cashier->id,
                'total' => $totals->total,
                'vat_breakdown' => $totals->vat,
            ]);

            $sale->items()->createMany(array_map(fn (array $line) => [
                'product_id' => $line['product']->id,
                'product_name' => $line['product']->name,
                'sku' => $line['product']->sku,
                'unit_type' => $line['product']->unit_type,
                'unit_price' => $line['product']->price,
                'vat_rate' => $line['product']->vat_rate,
                'quantity' => $line['quantity'],
                'line_total' => $line['line_total'],
            ], $totals->lines));

            $sale->refresh();

            $this->stock->sellAtCounter(
                collect($totals->lines)->mapWithKeys(fn ($line) => [$line['product']->id => $line['quantity']])->all(),
                $cashier,
                $sale->code,
            );

            $sale->payment()->create([
                'method' => $payment['method'],
                'amount' => $totals->total,
                'tendered' => $payment['tendered'],
                'change' => $payment['change'],
                'reference' => $payment['reference'],
                'user_id' => $cashier->id,
            ]);

            AuditLog::record('sale', "Venta {$sale->code} ({$payment['method']->label()}, ".number_format($totals->total, 2, ',', '.').' €)', $sale, user: $cashier);

            return $sale;
        });
    }
}
