<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    protected $fillable = ['on_hand', 'store_qty', 'online_qty', 'reserved_qty', 'min_alert'];

    protected $attributes = [
        'on_hand' => 0,
        'store_qty' => 0,
        'online_qty' => 0,
        'reserved_qty' => 0,
        'min_alert' => 0,
    ];

    protected function casts(): array
    {
        return [
            'on_hand' => 'float',
            'store_qty' => 'float',
            'online_qty' => 'float',
            'reserved_qty' => 'float',
            'min_alert' => 'float',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** What the web and the assistant may sell: online stock minus reservations. */
    protected function availableOnline(): Attribute
    {
        return Attribute::get(fn () => max(0, round($this->online_qty - $this->reserved_qty, 3)));
    }

    protected function isBelowMinimum(): Attribute
    {
        return Attribute::get(fn () => $this->min_alert > 0 && $this->on_hand < $this->min_alert);
    }

    public function scopeBelowMinimum(Builder $query): Builder
    {
        return $query->where('min_alert', '>', 0)->whereColumn('on_hand', '<', 'min_alert');
    }
}
