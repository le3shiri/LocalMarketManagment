<?php

namespace App\Console\Commands;

use App\Services\ExpiryService;
use Illuminate\Console\Command;

class SendExpiryAlerts extends Command
{
    protected $signature = 'stock:expiry-alerts';

    protected $description = 'Avisa al personal de almacén de los lotes que caducan pronto';

    public function handle(ExpiryService $expiry): int
    {
        $count = $expiry->sendAlerts();

        $this->info($count ? "Aviso enviado: {$count} lote(s) caducan pronto." : 'Ningún lote nuevo que avisar hoy.');

        return self::SUCCESS;
    }
}
