<?php

use App\Models\AuditLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sensitive flag + hash chain: each entry stores the hash of the previous one, so editing
 * or deleting any past entry breaks the chain and is detected by audit:verify.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->boolean('is_sensitive')->default(false)->after('action')->index();
            $table->char('previous_hash', 64)->nullable()->after('ip_address');
            $table->char('hash', 64)->nullable()->after('previous_hash');
        });

        // Chain the entries that already exist, in order.
        $previous = null;
        DB::table('audit_logs')->orderBy('id')->each(function ($row) use (&$previous) {
            $hash = AuditLog::computeHash($previous, (array) $row);
            DB::table('audit_logs')->where('id', $row->id)->update(['previous_hash' => $previous, 'hash' => $hash]);
            $previous = $hash;
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['is_sensitive']);
            $table->dropColumn(['is_sensitive', 'previous_hash', 'hash']);
        });
    }
};
