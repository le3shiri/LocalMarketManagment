<?php

namespace App\Console\Commands;

use App\Services\ExpiryService;
use Illuminate\Console\Command;

class BlockExpiredBatches extends Command
{
    protected $signature = 'stock:block-expired';

    protected $description = 'Retira del stock vendible las unidades de los lotes caducados';

    public function handle(ExpiryService $expiry): int
    {
        $batches = $expiry->blockExpired();

        foreach ($batches as $batch) {
            $this->line("· {$batch->product->name} — lote {$batch->lot_code} (caducó el {$batch->expires_at->format('d/m/Y')})");
        }

        $this->info($batches->isEmpty() ? 'No hay lotes caducados.' : "{$batches->count()} lote(s) caducado(s) bloqueados.");

        return self::SUCCESS;
    }
}
