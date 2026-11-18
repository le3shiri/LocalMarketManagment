<?php

namespace App\Models;

use App\Enums\UnitType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'product_name', 'sku', 'unit_type', 'unit_price', 'vat_rate', 'quantity', 'line_total'];

    protected function casts(): array
    {
        return [
            'unit_type' => UnitType::class,
            'unit_price' => 'decimal:2',
            'vat_rate' => 'integer',
            'quantity' => 'float',
            'line_total' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function refundItems(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }

    /** Quantity that can still be returned. */
    public function refundableQuantity(): float
    {
        return max(0, round($this->quantity - (float) $this->refundItems()->sum('quantity'), 3));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
