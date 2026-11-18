<?php

namespace App\Models;

use App\Enums\WriteOffType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WriteOff extends Model
{
    protected $fillable = ['product_id', 'stock_batch_id', 'user_id', 'type', 'channel', 'quantity', 'value', 'notes'];

    protected function casts(): array
    {
        return ['type' => WriteOffType::class, 'quantity' => 'float', 'value' => 'decimal:2'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
