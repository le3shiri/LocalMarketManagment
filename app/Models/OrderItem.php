<?php

namespace App\Models;

use App\Enums\UnitType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['product_id', 'product_name', 'sku', 'unit_type', 'unit_price', 'quantity', 'line_total'];

    protected function casts(): array
    {
        return [
            'unit_type' => UnitType::class,
            'unit_price' => 'decimal:2',
            'quantity' => 'float',
            'line_total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
