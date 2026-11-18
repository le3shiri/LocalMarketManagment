<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;

/**
 * Records create / update / delete of the model in the audit log.
 * Models can define $auditLabel (e.g. "producto") and $auditExcept (attributes never logged).
 */
trait Auditable
{
    private const ALWAYS_HIDDEN = ['password', 'remember_token', 'updated_at', 'created_at'];

    public static function bootAuditable(): void
    {
        static::created(fn ($model) => $model->audit('created', 'Alta de '));
        static::updated(fn ($model) => $model->audit('updated', 'Modificación de '));
        static::deleted(fn ($model) => $model->audit('deleted', 'Eliminación de '));
    }

    protected function audit(string $action, string $prefix): void
    {
        $changes = null;

        if ($action === 'updated') {
            $hidden = array_merge(self::ALWAYS_HIDDEN, $this->auditExcept ?? []);

            $changes = collect($this->getChanges())->except($hidden)
                ->mapWithKeys(fn ($value, $key) => [$key => [
                    'antes' => self::auditValue($this->getRawOriginal($key)),
                    'después' => self::auditValue($value),
                ]])->all();

            // Password changes are worth knowing about, never their value.
            if (array_key_exists('password', $this->getChanges())) {
                $changes['password'] = ['antes' => '•••', 'después' => '•••'];
            }

            if ($changes === []) {
                return; // only timestamps or ignored fields changed
            }
        }

        AuditLog::record($action, $prefix.($this->auditLabel ?? class_basename($this)).' «'.$this->auditName().'»', $this, $changes);
    }

    /** Store values as plain strings so before/after are comparable regardless of casts. */
    private static function auditValue(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            $value instanceof \BackedEnum => (string) $value->value,
            is_bool($value) => $value ? '1' : '0',
            is_float($value) => number_format($value, 2, '.', ''),
            default => (string) $value,
        };
    }

    protected function auditName(): string
    {
        return (string) ($this->code ?? $this->name ?? $this->getKey());
    }
}
