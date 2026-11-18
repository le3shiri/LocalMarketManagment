<?php

namespace App\Console\Commands;

use App\Services\AuditChain;
use Illuminate\Console\Command;

class VerifyAuditLog extends Command
{
    protected $signature = 'audit:verify';

    protected $description = 'Comprueba que nadie ha modificado ni borrado entradas del historial de operaciones';

    public function handle(AuditChain $chain): int
    {
        $result = $chain->verify();

        if ($result['valid']) {
            $this->info("Historial íntegro: {$result['checked']} entradas verificadas.");

            return self::SUCCESS;
        }

        $this->error("Historial manipulado: la cadena se rompe en la entrada #{$result['broken_at']} (tras {$result['checked']} entradas correctas).");

        return self::FAILURE;
    }
}
