<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ErDiagramCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagram_is_generated_from_the_real_schema(): void
    {
        Artisan::call('docs:er-diagram');
        $diagram = Artisan::output();

        $this->assertStringStartsWith('erDiagram', $diagram);
        $this->assertStringContainsString('    orders {', $diagram);
        $this->assertStringContainsString('bigint id PK', $diagram);
        $this->assertStringContainsString('varchar code UK', $diagram);
        $this->assertStringContainsString('categories ||--o{ products : "category_id"', $diagram);
        $this->assertStringContainsString('users |o--o{ orders : "user_id"', $diagram, 'Nullable FK shown as optional');
        $this->assertStringNotContainsString('    migrations {', $diagram, 'Framework tables are hidden');
    }

    public function test_committed_documentation_matches_the_current_schema(): void
    {
        Artisan::call('docs:er-diagram');

        // Compare tables, columns and relationships, not types: SQLite and MySQL name types differently.
        // If this fails after adding a migration, run: php artisan docs:er-diagram --write
        $this->assertSame(
            $this->structure(Artisan::output()),
            $this->structure(file_get_contents(base_path('docs/MODELO_DE_DATOS.md'))),
        );
    }

    /** @return list<string> "table.column" and relationship lines, sorted */
    private function structure(string $text): array
    {
        $lines = [];
        $table = null;

        foreach (preg_split('/\R/', $text) as $line) {
            if (preg_match('/^    (\w+) \{$/', $line, $m)) {
                $table = $m[1];
            } elseif (preg_match('/^        \w+ (\w+)/', $line, $m) && $table) {
                $lines[] = "{$table}.{$m[1]}";
            } elseif (str_contains($line, '--o{')) {
                $lines[] = trim($line);
            }
        }

        sort($lines);

        return $lines;
    }
}
