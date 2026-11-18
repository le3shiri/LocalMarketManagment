<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

/**
 * Re-computes the audit log hash chain to detect edited or deleted entries.
 */
class AuditChain
{
    /**
     * @return array{valid: bool, checked: int, broken_at: ?int}
     */
    public function verify(): array
    {
        $previous = null;
        $checked = 0;

        foreach (DB::table('audit_logs')->orderBy('id')->lazy(500) as $row) {
            $row = (array) $row;

            if ($row['previous_hash'] !== $previous || AuditLog::computeHash($previous, $row) !== $row['hash']) {
                return ['valid' => false, 'checked' => $checked, 'broken_at' => $row['id']];
            }

            $previous = $row['hash'];
            $checked++;
        }

        return ['valid' => true, 'checked' => $checked, 'broken_at' => null];
    }
}
