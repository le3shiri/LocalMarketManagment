<?php

namespace App\Support;

use App\Models\AuditLog;
use Closure;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Spreadsheet-friendly CSV downloads used by every report: UTF-8 with BOM (Excel shows
 * accents correctly), ';' separator and decimal comma (Spanish locale). Rows are streamed,
 * so large exports don't load everything into memory. Each export is audited.
 */
final class CsvExport
{
    /**
     * @param  list<string>  $headings
     * @param  Closure(Closure(array): void): void  $rows  receives a writer to call once per row
     */
    public static function download(string $name, array $headings, Closure $rows, string $description): StreamedResponse
    {
        AuditLog::record('export', "Exportación: {$description}");

        $filename = $name.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headings, ';');

            $rows(function (array $row) use ($out) {
                fputcsv($out, array_map([self::class, 'cell'], $row), ';');
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Amounts always with two decimals ("43,40"). */
    public static function money(float|string|null $amount): string
    {
        return number_format((float) $amount, 2, ',', '');
    }

    /** Numbers with decimal comma, booleans as Sí/No, dates as d/m/Y H:i. */
    public static function cell(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'Sí' : 'No',
            is_float($value) => str_replace('.', ',', rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.')),
            $value instanceof \DateTimeInterface => $value->format('d/m/Y H:i'),
            $value instanceof \BackedEnum => method_exists($value, 'label') ? $value->label() : (string) $value->value,
            default => (string) $value,
        };
    }
}
