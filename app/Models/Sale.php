<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Sale extends Model
{
    protected $fillable = ['user_id', 'total', 'vat_breakdown'];

    protected function casts(): array
    {
        return ['total' => 'decimal:2', 'vat_breakdown' => 'array'];
    }

    protected static function booted(): void
    {
        // Correlative ticket number derived from the id (race-free).
        static::created(fn (Sale $sale) => $sale->forceFill(['code' => 'T-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT)])->saveQuietly());
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
