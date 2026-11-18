<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Database backups and restore.
 *  - MySQL: mysqldump (consistent snapshot with --single-transaction), gzip-compressed.
 *  - SQLite: VACUUM INTO (consistent copy, also works on an in-memory database), gzip-compressed.
 * Backups rotate (config backup.keep) and every backup/restore is written to the audit log.
 */
class BackupService
{
    /** Only files we created can be listed, downloaded or restored. */
    public const NAME_PATTERN = '/^localmarket-\d{8}-\d{6}(-\d+)?\.(sql|sqlite)\.gz$/';

    public function __construct(private ?string $connection = null) {}

    public function onConnection(string $connection): static
    {
        return new static($connection);
    }

    /** Create a backup and return its file name. */
    public function create(?User $user = null, string $reason = 'manual'): string
    {
        File::ensureDirectoryExists($this->directory());

        $driver = $this->config('driver');
        $extension = $driver === 'sqlite' ? '.sqlite.gz' : '.sql.gz';
        $stamp = 'localmarket-'.now()->format('Ymd-His');

        // Never overwrite an existing backup (two backups in the same second).
        $name = $stamp.$extension;
        for ($i = 2; File::exists($this->path($name)); $i++) {
            $name = "{$stamp}-{$i}{$extension}";
        }
        $path = $this->path($name);

        match ($driver) {
            'sqlite' => $this->dumpSqlite($path),
            'mysql', 'mariadb' => $this->dumpMysql($path),
            default => throw new RuntimeException("Driver de base de datos no soportado para copias: {$driver}"),
        };

        $this->rotate();

        AuditLog::record('backup', "Copia de seguridad {$name} ({$reason}, ".$this->humanSize(filesize($path)).')', user: $user);

        return $name;
    }

    /**
     * Restore a backup. A safety backup of the current data is taken first, so a restore
     * can always be undone.
     *
     * @return string name of the safety backup taken before restoring
     */
    public function restore(string $name, ?User $user = null): string
    {
        // Read the backup before anything else touches the backup folder.
        $dump = gzdecode(File::get($this->existing($name)));

        if ($dump === false || $dump === '') {
            throw new RuntimeException("La copia «{$name}» está dañada o vacía.");
        }

        $safety = $this->create($user, "antes de restaurar {$name}");

        match ($this->config('driver')) {
            'sqlite' => $this->restoreSqlite($dump),
            'mysql', 'mariadb' => $this->restoreMysql($dump),
        };

        AuditLog::record('backup', "Restauración de la copia {$name} (copia previa: {$safety})", user: $user);

        return $safety;
    }

    /** @return Collection<int, array{name: string, size: int, created_at: \Illuminate\Support\Carbon}> newest first */
    public function list(): Collection
    {
        if (! File::isDirectory($this->directory())) {
            return collect();
        }

        return collect(File::files($this->directory()))
            ->filter(fn ($file) => preg_match(self::NAME_PATTERN, $file->getFilename()))
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'created_at' => \Illuminate\Support\Carbon::createFromTimestamp($file->getMTime()),
            ])
            ->sortByDesc('name')
            ->values();
    }

    /** Absolute path of an existing backup; rejects anything that isn't one of ours. */
    public function existing(string $name): string
    {
        if (! preg_match(self::NAME_PATTERN, $name) || ! File::exists($this->path($name))) {
            throw new RuntimeException("La copia «{$name}» no existe.");
        }

        return $this->path($name);
    }

    public function humanSize(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1).' MB' : max(1, round($bytes / 1024)).' KB';
    }

    private function dumpSqlite(string $path): void
    {
        $db = DB::connection($this->connection);

        if ($db->transactionLevel() > 0) {
            throw new RuntimeException('No se puede copiar una base de datos SQLite dentro de una transacción abierta.');
        }

        $tmp = $path.'.tmp';
        $db->statement('VACUUM INTO ?', [$tmp]);
        $this->gzip($tmp, $path);
        File::delete($tmp);
    }

    private function restoreSqlite(string $dump): void
    {
        $database = $this->config('database');

        if ($database === ':memory:' || ! File::exists($database)) {
            throw new RuntimeException('Solo se puede restaurar sobre una base de datos SQLite en fichero.');
        }

        if (! str_starts_with($dump, "SQLite format 3\0")) {
            throw new RuntimeException('La copia no es una base de datos SQLite válida.');
        }

        DB::purge($this->connection); // release the file before replacing it
        File::put($database, $dump);
        DB::reconnect($this->connection);
    }

    private function dumpMysql(string $path): void
    {
        $result = Process::env(['MYSQL_PWD' => (string) $this->config('password')])
            ->timeout(600)
            ->run([
                config('backup.mysqldump'), '--single-transaction', '--routines', '--no-tablespaces', '--default-character-set=utf8mb4',
                '-h', (string) $this->config('host'), '-P', (string) $this->config('port'), '-u', (string) $this->config('username'),
                (string) $this->config('database'),
            ]);

        if (! $result->successful()) {
            throw new RuntimeException('mysqldump ha fallado: '.trim($result->errorOutput()));
        }

        File::put($path, gzencode($result->output(), 9));
    }

    private function restoreMysql(string $dump): void
    {
        $result = Process::env(['MYSQL_PWD' => (string) $this->config('password')])
            ->timeout(600)
            ->input($dump)
            ->run([
                config('backup.mysql'), '--default-character-set=utf8mb4',
                '-h', (string) $this->config('host'), '-P', (string) $this->config('port'), '-u', (string) $this->config('username'),
                (string) $this->config('database'),
            ]);

        if (! $result->successful()) {
            throw new RuntimeException('La restauración ha fallado: '.trim($result->errorOutput()));
        }
    }

    private function gzip(string $source, string $target): void
    {
        File::put($target, gzencode(File::get($source), 9));
    }

    private function rotate(): void
    {
        $this->list()->slice(max(1, (int) config('backup.keep')))
            ->each(fn ($backup) => File::delete($this->path($backup['name'])));
    }

    private function directory(): string
    {
        return rtrim(config('backup.path'), '/\\');
    }

    private function path(string $name): string
    {
        return $this->directory().DIRECTORY_SEPARATOR.$name;
    }

    private function config(string $key): mixed
    {
        $connection = $this->connection ?? config('database.default');

        return config("database.connections.{$connection}.{$key}");
    }
}
