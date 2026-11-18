<?php

namespace App\Models;

use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'created_by', 'channel', 'source_reference', 'source_message', 'status', 'fulfillment', 'contact_name', 'contact_phone',
        'delivery_address', 'slot_date', 'slot', 'subtotal', 'delivery_fee', 'total', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'fulfillment' => Fulfillment::class,
            'slot_date' => 'date',
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        // The code is derived from the auto-increment id, so it is correlative and race-free.
        static::created(function (Order $order) {
            $order->forceFill(['code' => static::codeFor($order->id)])->saveQuietly();
        });
    }

    public static function codeFor(int $id): string
    {
        return 'LM-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    public function channelLabel(): string
    {
        return match ($this->channel) {
            'web' => 'Web',
            'phone' => 'Teléfono',
            'whatsapp' => 'WhatsApp',
            default => ucfirst($this->channel),
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Employee who took a phone / WhatsApp order. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusChanges(): HasMany
    {
        return $this->hasMany(OrderStatusChange::class)->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
