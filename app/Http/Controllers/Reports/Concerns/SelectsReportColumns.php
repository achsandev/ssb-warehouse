<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports\Concerns;

/**
 * Helper trait untuk export controller yang mendukung column selection.
 *
 * Setiap controller mendefinisikan map `COLUMN_DEFS` (key => header label)
 * dan implementasi `valueFor($row, $key)`. Trait ini menangani:
 *   - Resolusi header berdasar urutan key terpilih.
 *   - Mapping row → array nilai sesuai key terpilih.
 *
 * Whitelist enforcement: key yang tidak terdaftar di COLUMN_DEFS otomatis
 * di-strip oleh `FilterRequest::selectedColumns()` — tidak perlu re-check
 * di sini.
 */
trait SelectsReportColumns
{
    /**
     * Bangun array header label dari key terpilih, mengikuti urutan key.
     *
     * @param  array<int, string>             $keys
     * @param  array<string, string>          $columnDefs   Map key => label
     * @return array<int, string>
     */
    protected function pickHeaders(array $keys, array $columnDefs): array
    {
        return array_values(array_map(
            static fn (string $k): string => $columnDefs[$k] ?? $k,
            $keys,
        ));
    }

    /**
     * Bangun array nilai row dari key terpilih, mengikuti urutan key.
     * `$valueResolver` adalah closure yang menerima ($row, $key) dan
     * mengembalikan cell value.
     *
     * @param  object|array                                            $row
     * @param  array<int, string>                                      $keys
     * @param  callable(object|array, string): mixed                   $valueResolver
     * @return array<int, mixed>
     */
    protected function pickRow(object|array $row, array $keys, callable $valueResolver): array
    {
        return array_values(array_map(
            static fn (string $k): mixed => $valueResolver($row, $k),
            $keys,
        ));
    }

    /**
     * Convert column index (1-based) ke huruf Excel-style:
     *   1 → A, 2 → B, 26 → Z, 27 → AA, 53 → BA.
     * Dipakai untuk menghitung endCol merged title ketika jumlah kolom dinamis.
     */
    protected function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = (int) (($index - $mod) / 26);
        }
        return $letter;
    }
}
