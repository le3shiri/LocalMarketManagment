<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'product_id', 'user_id', 'type', 'store_delta', 'online_delta', 'reserved_delta',
        'on_hand_after', 'reference', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => MovementType::class,
            'store_delta' => 'float',
            'online_delta' => 'float',
            'reserved_delta' => 'float',
            'on_hand_after' => 'float',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
