<?php

namespace App\Support;

use App\Models\Product;

/**
 * Totals of a counter sale. Prices already include VAT (as in any Spanish shop), so the
 * breakdown extracts base and tax per rate: base = total / (1 + rate).
 */
final class SaleTotals
{
    /**
     * @param  list<array{product: Product, quantity: float, line_total: float}>  $lines
     * @param  array<int, array{base: float, vat: float, total: float}>  $vat  rate => amounts
     */
    private function __construct(
        public readonly array $lines,
        public readonly float $total,
        public readonly array $vat,
    ) {}

    /** @param  iterable<array{product: Product, quantity: float}>  $items */
    public static function for(iterable $items): self
    {
        $lines = [];
        $byRate = [];

        foreach ($items as $item) {
            $lineTotal = round((float) $item['product']->price * $item['quantity'], 2);
            $lines[] = $item + ['line_total' => $lineTotal];

            $rate = (int) $item['product']->vat_rate;
            $byRate[$rate] = ($byRate[$rate] ?? 0) + $lineTotal;
        }

        ksort($byRate);

        $vat = [];
        foreach ($byRate as $rate => $gross) {
            $base = round($gross / (1 + $rate / 100), 2);
            $vat[$rate] = ['base' => $base, 'vat' => round($gross - $base, 2), 'total' => round($gross, 2)];
        }

        return new self($lines, round(array_sum($byRate), 2), $vat);
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }
}
