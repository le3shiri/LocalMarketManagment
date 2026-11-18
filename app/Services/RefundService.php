<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Refund;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Returns of counter sales, following the shop rules:
 *  - within config('shop.refund_days') days of the sale, with a reason;
 *  - never more than was sold minus what was already returned;
 *  - money goes back with the same method used to pay;
 *  - perishable products are refunded but not put back on the shelf.
 */
class RefundService
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * @param  array<int, float>  $quantities  sale_item_id => quantity to return
     *
     * @throws InvalidArgumentException with a user-facing message
     */
    public function refund(Sale $sale, array $quantities, string $reason, User $user): Refund
    {
        $days = (int) config('shop.refund_days');

        if ($sale->created_at->lt(now()->subDays($days)->startOfDay())) {
            throw new InvalidArgumentException("El ticket {$sale->code} tiene más de {$days} días: no admite devoluciones.");
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('Indica el motivo de la devolución.');
        }

        $quantities = array_filter(array_map(fn ($q) => round((float) $q, 3), $quantities), fn ($q) => $q > 0);

        if ($quantities === []) {
            throw new InvalidArgumentException('Indica al menos una cantidad a devolver.');
        }

        return DB::transaction(function () use ($sale, $quantities, $reason, $user) {
            // Lock the sale so two refunds of the same ticket can't overlap.
            Sale::whereKey($sale->id)->lockForUpdate()->first();

            $items = $sale->items()->with('product')->whereIn('id', array_keys($quantities))->get()->keyBy('id');
            $lines = [];
            $restock = [];

            foreach ($quantities as $itemId => $quantity) {
                /** @var SaleItem|null $item */
                $item = $items->get($itemId) ?? throw new InvalidArgumentException('La línea no pertenece a este ticket.');
                $refundable = $item->refundableQuantity();

                if ($quantity > $refundable + 0.0005) {
                    throw new InvalidArgumentException("De «{$item->product_name}» solo se pueden devolver ".rtrim(rtrim(number_format($refundable, 3, ',', ''), '0'), ',')." {$item->unit_type->suffix()}.");
                }

                if ($item->unit_type->step() >= 1 && fmod($quantity, 1) !== 0.0) {
                    throw new InvalidArgumentException("«{$item->product_name}» se devuelve por unidades enteras.");
                }

                $canRestock = $item->product && ! $item->product->is_perishable;
                $lines[] = [
                    'sale_item_id' => $item->id,
                    'quantity' => $quantity,
                    // Full line refunded => exactly what was charged; otherwise price × quantity.
                    'amount' => abs($quantity - $item->quantity) < 0.0005 ? (float) $item->line_total : round((float) $item->unit_price * $quantity, 2),
                    'restocked' => $canRestock,
                ];

                if ($canRestock) {
                    $restock[$item->product_id] = ($restock[$item->product_id] ?? 0) + $quantity;
                }
            }

            $amount = round(array_sum(array_column($lines, 'amount')), 2);

            $refund = Refund::create(['sale_id' => $sale->id, 'user_id' => $user->id, 'reason' => $reason, 'amount' => $amount]);
            $refund->items()->createMany($lines);
            $refund->refresh();

            $this->stock->restockReturn($restock, $user, $refund->code, $reason);

            $refund->payment()->create([
                'method' => $sale->payment?->method ?? 'cash',
                'amount' => -$amount,
                'user_id' => $user->id,
            ]);

            AuditLog::record('refund', "Devolución {$refund->code} del ticket {$sale->code} (".number_format($amount, 2, ',', '.').' €)', $refund, ['motivo' => $reason], $user);

            return $refund;
        });
    }
}
