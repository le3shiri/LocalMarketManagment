<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBatch extends Model
{
    public const ACTIVE = 'active';
    public const EXPIRED = 'expired';
    public const WITHDRAWN = 'withdrawn';

    protected $fillable = ['product_id', 'lot_code', 'expires_at', 'quantity_received', 'quantity_remaining', 'status', 'received_by', 'reference'];

    protected $attributes = ['status' => self::ACTIVE];

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'quantity_received' => 'float',
            'quantity_remaining' => 'float',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /** Active batches that still have units, first-expiring first (FEFO). */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', self::ACTIVE)
            ->where('quantity_remaining', '>', 0)
            ->orderByRaw('expires_at is null')   // batches without date go last
            ->orderBy('expires_at')
            ->orderBy('id');
    }

    /** Whole days until expiry (negative when already expired). */
    protected function daysLeft(): Attribute
    {
        return Attribute::get(fn () => $this->expires_at ? (int) today()->diffInDays($this->expires_at, false) : null);
    }
}
