<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

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
        'refund' => 'Devolución',
        'backup' => 'Copia de seguridad',
    ];

    /** Actions that are always sensitive. */
    private const SENSITIVE_ACTIONS = ['permissions', 'refund', 'login_failed', 'deleted', 'backup'];

    /** Attribute changes that make an "updated" entry sensitive, per model. */
    private const SENSITIVE_FIELDS = [
        User::class => ['role_id', 'is_active', 'password', 'email'],
        Product::class => ['price', 'vat_rate', 'is_active'],
    ];

    protected $fillable = ['user_id', 'action', 'is_sensitive', 'subject_type', 'subject_id', 'description', 'changes', 'ip_address'];

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime', 'is_sensitive' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeSensitive(Builder $query): Builder
    {
        return $query->where('is_sensitive', true);
    }

    /**
     * Append an entry to the log. Entries are chained: each stores the previous entry's hash.
     *
     * @param  bool  $sensitive  force the sensitive flag (stock adjustments, write-offs, settings…)
     */
    public static function record(string $action, string $description, ?Model $subject = null, ?array $changes = null, ?User $user = null, bool $sensitive = false): self
    {
        return DB::transaction(function () use ($action, $description, $subject, $changes, $user, $sensitive) {
            $log = new static([
                'user_id' => $user?->id ?? auth()->id(),
                'action' => $action,
                'is_sensitive' => $sensitive || static::isSensitive($action, $subject, $changes),
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'changes' => $changes ?: null,
                'ip_address' => request()->ip(),
            ]);
            $log->created_at = now()->startOfSecond();

            // Lock the tail of the chain so concurrent writers can't fork it.
            $previous = static::query()->latest('id')->lockForUpdate()->value('hash');
            $log->previous_hash = $previous;
            $log->hash = static::computeHash($previous, $log->getAttributes());
            $log->save();

            return $log;
        });
    }

    public static function isSensitive(string $action, ?Model $subject, ?array $changes): bool
    {
        if (in_array($action, self::SENSITIVE_ACTIONS, true)) {
            return true;
        }

        if ($action === 'updated' && $subject && $changes) {
            $fields = self::SENSITIVE_FIELDS[$subject::class] ?? [];

            return array_intersect($fields, array_keys($changes)) !== [];
        }

        return false;
    }

    /**
     * SHA-256 over the previous hash and the entry's stored (raw) values.
     *
     * @param  array<string, mixed>  $row  raw attributes as stored in the database
     */
    public static function computeHash(?string $previous, array $row): string
    {
        $createdAt = $row['created_at'] instanceof \DateTimeInterface ? $row['created_at']->format('Y-m-d H:i:s') : (string) $row['created_at'];

        return hash('sha256', implode('|', [
            $previous ?? 'GENESIS',
            $row['user_id'] ?? '',
            $row['action'],
            (int) (bool) ($row['is_sensitive'] ?? false),
            $row['subject_type'] ?? '',
            $row['subject_id'] ?? '',
            $row['description'],
            $row['changes'] ?? '',
            $row['ip_address'] ?? '',
            substr($createdAt, 0, 19),
        ]));
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}
