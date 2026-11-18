<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Throwable;

class RunBackup extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Crea una copia de seguridad comprimida de la base de datos';

    public function handle(BackupService $backups): int
    {
        try {
            $name = $backups->create(reason: 'programada');
        } catch (Throwable $e) {
            $this->error('No se ha podido crear la copia: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Copia creada: {$name}");

        return self::SUCCESS;
    }
}
