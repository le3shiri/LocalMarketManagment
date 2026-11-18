<?php

namespace App\Exceptions;

use App\Models\ProductStock;
use Illuminate\Support\Collection;

/**
 * Thrown when an order can't be reserved because online stock changed. Extends CartException
 * so the checkout can show its message to the customer like any other cart problem.
 */
class InsufficientStockException extends CartException
{
    /**
     * @param  array<int, float>  $shortages  product_id => quantity still available online
     * @param  Collection<int, ProductStock>  $stocks
     */
    public function __construct(public readonly array $shortages, Collection $stocks)
    {
        $lines = collect($shortages)->map(function (float $available, int $productId) use ($stocks) {
            $product = $stocks->get($productId)?->product;
            $name = $product?->name ?? 'Un producto';

            return $available > 0 && $product
                ? "«{$name}»: solo quedan ".$product->formatQuantity($available)
                : "«{$name}»: agotado";
        });

        parent::__construct('El stock ha cambiado mientras completabas el pedido. '.$lines->implode('; ').'. Revisa tu carrito.');
    }
}
