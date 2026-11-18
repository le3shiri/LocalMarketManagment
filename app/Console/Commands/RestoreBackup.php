<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Restoring replaces ALL current data, so it is only available from the command line,
 * asks for explicit confirmation and always takes a safety backup first.
 */
class RestoreBackup extends Command
{
    protected $signature = 'backup:restore {file? : Nombre de la copia (localmarket-AAAAMMDD-HHMMSS…)} {--force : No pedir confirmación}';

    protected $description = 'Restaura una copia de seguridad (hace antes una copia de los datos actuales)';

    public function handle(BackupService $backups): int
    {
        $available = $backups->list();

        if ($available->isEmpty()) {
            $this->error('No hay copias de seguridad.');

            return self::FAILURE;
        }

        $file = $this->argument('file') ?? $this->choice('¿Qué copia quieres restaurar?', $available->pluck('name')->all(), 0);

        if (! $this->option('force') && ! $this->confirm("Se van a REEMPLAZAR todos los datos actuales por los de {$file}. ¿Continuar?")) {
            $this->warn('Restauración cancelada.');

            return self::FAILURE;
        }

        try {
            $safety = $backups->restore($file);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Copia {$file} restaurada. Los datos anteriores están en {$safety}.");

        return self::SUCCESS;
    }
}
