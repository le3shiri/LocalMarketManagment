<?php

namespace App\Models;

use App\Enums\Availability;
use App\Enums\UnitType;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'sku', 'description', 'price', 'unit_type',
        'is_perishable', 'location', 'image_path',
    ];

    protected $attributes = [
        'is_active' => true,
        'unit_type' => 'unit',
        'is_perishable' => false,
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'unit_type' => UnitType::class,
            'is_perishable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if ($product->isDirty('name') || ! $product->slug) {
                $product->slug = static::uniqueSlug($product);
            }
        });

        // Every product has exactly one stock row, starting at zero.
        static::created(fn (Product $product) => $product->stock()->create());
    }

    public function stock(): HasOne
    {
        return $this->hasOne(ProductStock::class)->withDefault();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image_path ? Storage::disk('public')->url($this->image_path) : null);
    }

    /** "2,40 € / kg" */
    protected function formattedPrice(): Attribute
    {
        return Attribute::get(fn () => Number::currency((float) $this->price, 'EUR', 'es').' / '.$this->unit_type->suffix());
    }

    /** Online availability as the web shop and the assistant see it. */
    public function availability(): Availability
    {
        return Availability::fromQuantity($this->stock->available_online);
    }

    /** "12 ud" or "1,25 kg" */
    public function formatQuantity(float $quantity): string
    {
        $decimals = $this->unit_type === UnitType::Weight ? 2 : 0;

        return Number::format($quantity, maxPrecision: $decimals, locale: 'es').' '.$this->unit_type->suffix();
    }

    private static function uniqueSlug(Product $product): string
    {
        $base = Str::slug($product->name);
        $slug = $base;

        for ($i = 2; static::where('slug', $slug)->whereKeyNot($product->getKey())->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
