<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['sale_item_id', 'quantity', 'amount', 'restocked'];

    protected function casts(): array
    {
        return ['quantity' => 'float', 'amount' => 'decimal:2', 'restocked' => 'boolean'];
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }
}
