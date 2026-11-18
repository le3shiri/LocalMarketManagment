<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /** action => label shown in the history screen */
    public const ACTIONS = [
        'login' => 'Inicio de sesión',
        'login_failed' => 'Intento de acceso fallido',
        'logout' => 'Cierre de sesión',
        'created' => 'Alta',
        'updated' => 'Modificación',
        'deleted' => 'Eliminación',
        'permissions' => 'Cambio de permisos',
        'stock' => 'Movimiento de stock',
        'order' => 'Pedido',
        'sale' => 'Venta en caja',
    ];

    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'description', 'changes', 'ip_address'];

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(string $action, string $description, ?Model $subject = null, ?array $changes = null, ?User $user = null): self
    {
        return static::create([
            'user_id' => $user?->id ?? auth()->id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'changes' => $changes ?: null,
            'ip_address' => request()->ip(),
        ]);
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}
