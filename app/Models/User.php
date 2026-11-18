<?php

namespace App\Models;

use Database\Factories\UserFactory;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Auditable, Notifiable;

    protected string $auditLabel = 'usuario';
    /** @var list<string> */
    protected array $auditExcept = ['email_verified_at'];

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'phone',
        'address',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Mirrors the column default so new instances are consistent before they are saved. */
    protected $attributes = [
        'is_active' => true,
    ];

    /** Permission names, memoised per request. */
    private ?Collection $permissionNames = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /** Customer account matching a phone number, ignoring spaces and the +34 prefix. */
    public static function findByPhone(?string $phone): ?self
    {
        $digits = static::normalizePhone($phone);

        if (strlen($digits) < 9) {
            return null;
        }

        return static::whereNotNull('phone')->get(['id', 'phone', 'name', 'email', 'role_id'])
            ->first(fn (User $user) => static::normalizePhone($user->phone) === $digits);
    }

    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return strlen($digits) > 9 && str_starts_with($digits, '34') ? substr($digits, 2) : $digits;
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    public function hasPermission(string $permission): bool
    {
        $this->permissionNames ??= $this->role?->permissions()->pluck('name') ?? collect();

        return $this->permissionNames->contains($permission);
    }

    public function forgetPermissionCache(): void
    {
        $this->permissionNames = null;
        $this->unsetRelation('role');
    }
}
