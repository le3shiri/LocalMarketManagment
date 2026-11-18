<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Refund extends Model
{
    protected $fillable = ['sale_id', 'user_id', 'reason', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::created(fn (Refund $refund) => $refund->forceFill(['code' => 'D-'.str_pad((string) $refund->id, 6, '0', STR_PAD_LEFT)])->saveQuietly());
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }

    /** Money given back: a negative payment with the method of the original sale. */
    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
