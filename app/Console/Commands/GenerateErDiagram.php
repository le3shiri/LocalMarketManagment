<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Generates the entity-relationship diagram (Mermaid) from the real database schema, so the
 * documentation can never drift from the migrations:
 *
 *   php artisan docs:er-diagram                 (prints it)
 *   php artisan docs:er-diagram --write         (updates docs/MODELO_DE_DATOS.md)
 */
class GenerateErDiagram extends Command
{
    protected $signature = 'docs:er-diagram {--write : Escribe docs/MODELO_DE_DATOS.md}';

    protected $description = 'Genera el diagrama entidad-relación (Mermaid) a partir del esquema real';

    /** Framework tables that say nothing about the business. */
    private const IGNORED = ['migrations', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions', 'password_reset_tokens'];

    public function handle(): int
    {
        // On MySQL getTables() lists every database on the server: keep only ours.
        $database = DB::connection()->getDatabaseName();
        $tables = collect(Schema::getTables())
            ->filter(fn ($t) => in_array($t['schema'] ?? null, [null, 'main', $database], true))
            ->pluck('name')
            ->unique()
            ->reject(fn ($t) => in_array($t, self::IGNORED, true))
            ->sort()
            ->values();

        $lines = ['erDiagram'];
        $relations = [];

        foreach ($tables as $table) {
            $foreign = collect(Schema::getForeignKeys($table));
            $fkColumns = $foreign->flatMap(fn ($fk) => $fk['columns'])->all();
            $indexes = collect(Schema::getIndexes($table));
            $primary = $indexes->where('primary', true)->flatMap(fn ($i) => $i['columns'])->all();
            $unique = $indexes->filter(fn ($i) => $i['unique'] && ! $i['primary'] && count($i['columns']) === 1)
                ->flatMap(fn ($i) => $i['columns'])->all();

            $lines[] = "    {$table} {";
            foreach (Schema::getColumns($table) as $column) {
                $key = match (true) {
                    in_array($column['name'], $primary, true) && in_array($column['name'], $fkColumns, true) => ' PK, FK',
                    in_array($column['name'], $primary, true) => ' PK',
                    in_array($column['name'], $fkColumns, true) => ' FK',
                    in_array($column['name'], $unique, true) => ' UK',
                    default => '',
                };
                $lines[] = "        {$this->type($column['type_name'])} {$column['name']}{$key}";
            }
            $lines[] = '    }';

            foreach ($foreign as $fk) {
                if (! $tables->contains($fk['foreign_table'])) {
                    continue;
                }
                $nullable = collect(Schema::getColumns($table))->firstWhere('name', $fk['columns'][0])['nullable'] ?? false;
                $relations[] = sprintf('    %s %s--o{ %s : "%s"', $fk['foreign_table'], $nullable ? '|o' : '||', $table, $fk['columns'][0]);
            }
        }

        $diagram = implode("\n", array_merge($lines, array_unique($relations)));

        if (! $this->option('write')) {
            $this->line($diagram);

            return self::SUCCESS;
        }

        $path = base_path('docs/MODELO_DE_DATOS.md');
        $content = File::exists($path) ? File::get($path) : "# Modelo de datos\n\n<!-- er:start -->\n<!-- er:end -->\n";
        $content = preg_replace('/<!-- er:start -->.*<!-- er:end -->/s', "<!-- er:start -->\n```mermaid\n{$diagram}\n```\n<!-- er:end -->", $content);
        File::put($path, $content);

        $this->info("Diagrama actualizado en docs/MODELO_DE_DATOS.md ({$tables->count()} tablas).");

        return self::SUCCESS;
    }

    /** Mermaid only accepts one word per type. */
    private function type(string $type): string
    {
        return match (true) {
            str_contains($type, 'int') => $type === 'tinyint' ? 'tinyint' : 'bigint',
            str_contains($type, 'char') || $type === 'string' => 'varchar',
            in_array($type, ['text', 'longtext', 'mediumtext'], true) => 'text',
            default => preg_replace('/\W/', '', $type) ?: 'varchar',
        };
    }
}
