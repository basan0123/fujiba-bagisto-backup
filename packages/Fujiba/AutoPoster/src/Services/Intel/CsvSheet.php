<?php

namespace Fujiba\AutoPoster\Services\Intel;

use RuntimeException;

/**
 * Small CSV helper for owner-facing intel sheets.
 *  - write(): UTF-8 with BOM (Excel on Japanese Windows shows ja/my names),
 *    atomic rename, refuses to leave a zero-size file behind.
 *  - read(): strips the BOM, converts Excel's Shift-JIS saves to UTF-8,
 *    returns rows keyed by trimmed lower-case header.
 */
final class CsvSheet
{
    /**
     * @param  list<string>  $header
     * @param  iterable<array>  $rows  each row keyed by header name (missing keys → '')
     * @return int  data rows written
     */
    public static function write(string $path, array $header, iterable $rows, bool $bom = true): int
    {
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException("cannot create {$dir}");
        }

        $tmp = $path.'.tmp'.getmypid();
        $fh = fopen($tmp, 'wb');
        if (! $fh) {
            throw new RuntimeException("cannot write {$tmp}");
        }
        if ($bom) {
            fwrite($fh, "\xEF\xBB\xBF");
        }
        fputcsv($fh, $header, ',', '"', '');
        $n = 0;
        foreach ($rows as $row) {
            fputcsv($fh, array_map(fn ($h) => self::cell($row[$h] ?? ''), $header), ',', '"', '');
            $n++;
        }
        fclose($fh);

        clearstatcache(true, $tmp);
        if (! is_file($tmp) || filesize($tmp) === 0) {
            @unlink($tmp);
            throw new RuntimeException("zero-size output refused: {$path}");
        }
        if (! rename($tmp, $path)) {
            @unlink($tmp);
            throw new RuntimeException("cannot move into place: {$path}");
        }

        return $n;
    }

    /**
     * @return array{header: list<string>, rows: list<array{_line:int}&array<string,string>>}
     */
    public static function read(string $path): array
    {
        if (! is_file($path) || filesize($path) === 0) {
            throw new RuntimeException("missing or empty CSV: {$path}");
        }
        $raw = file_get_contents($path);
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        } elseif (! mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'SJIS-win');   // Excel "CSV" save on Japanese Windows
        }

        $fh = fopen('php://temp', 'w+b');
        fwrite($fh, $raw);
        rewind($fh);

        $header = null;
        $rows = [];
        $line = 0;
        while (($cells = fgetcsv($fh, 0, ',', '"', '')) !== false) {
            $line++;
            if ($cells === [null]) {
                continue;                                             // blank line
            }
            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim((string) $h)), $cells);

                continue;
            }
            if (count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }
            $row = ['_line' => $line];
            foreach ($header as $i => $h) {
                if ($h !== '') {
                    $row[$h] = trim((string) ($cells[$i] ?? ''));
                }
            }
            $rows[] = $row;
        }
        fclose($fh);

        if ($header === null) {
            throw new RuntimeException("CSV has no header: {$path}");
        }

        return ['header' => $header, 'rows' => $rows];
    }

    private static function cell(mixed $v): string
    {
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }
        if (is_float($v)) {
            return OwnerSheetValues::fmt($v);
        }

        return (string) ($v ?? '');
    }
}
