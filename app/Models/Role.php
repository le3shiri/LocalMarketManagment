<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const ADMIN = 'admin';
    public const CASHIER = 'cajero';
    public const WAREHOUSE = 'almacen';
    public const CUSTOMER = 'cliente';

    protected $fillable = ['name', 'label', 'description'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isAdmin(): bool
    {
        return $this->name === self::ADMIN;
    }

    public static function idFor(string $name): ?int
    {
        return static::where('name', $name)->value('id');
    }
}
