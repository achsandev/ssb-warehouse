<?php

namespace App\Http\Controllers\Reports\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Trait bersama untuk seluruh report controller.
 * Menyediakan helper:
 *  - applyDateRange(): filter start_date / end_date pada kolom tanggal spesifik.
 *  - streamReportCsv(): stream CSV (UTF-8 BOM) dengan chunkById agar hemat memori.
 *  - buildReportFilename(): format nama file konsisten antar report.
 */
trait StreamsReportCsv
{
    /**
     * Apply rentang tanggal berdasarkan kolom tanggal utama report.
     */
    protected function applyDateRange(Builder|QueryBuilder $query, string $dateColumn, Request $request): void
    {
        if ($start = $request->input('start_date')) {
            $query->whereDate($dateColumn, '>=', $start);
        }

        if ($end = $request->input('end_date')) {
            $query->whereDate($dateColumn, '<=', $end);
        }
    }

    /**
     * Stream query menjadi CSV. Pemanggil mengontrol header kolom dan mapping row.
     *
     * @param  Builder|QueryBuilder  $query           Query siap eksekusi (belum orderBy, chunkById akan meng-order).
     * @param  array                 $csvHeaders      Header CSV.
     * @param  Closure               $rowMapper       fn($row) => array untuk tiap baris.
     * @param  string                $slug            Slug report untuk filename (mis. 'stock-usage-report').
     * @param  Request               $request         Untuk membangun suffix filename dari rentang.
     * @param  string                $orderColumn     Kolom untuk chunkById (full qualified, mis. 'wh_xxx.id').
     * @param  string                $orderAlias      Alias kolom pada hasil select ('id' / 'detail_id').
     * @param  int                   $chunk           Ukuran chunk.
     */
    protected function streamReportCsv(
        Builder|QueryBuilder $query,
        array $csvHeaders,
        Closure $rowMapper,
        string $slug,
        Request $request,
        string $orderColumn,
        string $orderAlias,
        int $chunk = 500,
        array $prependRows = [],
    ): StreamedResponse {
        $filename = $this->buildReportFilename($slug, $request);

        $headers = [
            'Content-Type'           => 'text/csv; charset=UTF-8',
            'Content-Disposition'    => 'attachment; filename="' . $filename . '"',
            'Cache-Control'          => 'no-store, no-cache, must-revalidate',
            'Pragma'                 => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];

        return response()->stream(function () use ($query, $csvHeaders, $rowMapper, $orderColumn, $orderAlias, $chunk, $prependRows) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM agar Excel render karakter non-ASCII dengan benar.
            fwrite($out, "\xEF\xBB\xBF");

            $this->writePrelude($out, $csvHeaders, $prependRows);

            $query->chunkById($chunk, function ($rows) use ($out, $rowMapper) {
                foreach ($rows as $row) {
                    fputcsv($out, array_merge([''], $rowMapper($row)));
                }
                if (function_exists('ob_flush')) {
                    @ob_flush();
                }
                flush();
            }, $orderColumn, $orderAlias);

            fclose($out);
        }, 200, $headers);
    }

    /**
     * Varian untuk query agregat (GROUP BY, DATEDIFF, dll) yang tidak cocok dengan
     * chunkById. Memakai cursor() sehingga data tetap di-stream baris demi baris.
     */
    protected function streamReportCsvFromCursor(
        Builder|QueryBuilder $query,
        array $csvHeaders,
        Closure $rowMapper,
        string $slug,
        Request $request,
        array $prependRows = [],
    ): StreamedResponse {
        $filename = $this->buildReportFilename($slug, $request);

        $headers = [
            'Content-Type'           => 'text/csv; charset=UTF-8',
            'Content-Disposition'    => 'attachment; filename="' . $filename . '"',
            'Cache-Control'          => 'no-store, no-cache, must-revalidate',
            'Pragma'                 => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];

        return response()->stream(function () use ($query, $csvHeaders, $rowMapper, $prependRows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $this->writePrelude($out, $csvHeaders, $prependRows);

            foreach ($query->cursor() as $row) {
                fputcsv($out, array_merge([''], $rowMapper($row)));
            }

            fclose($out);
        }, 200, $headers);
    }

    /**
     * Tulis pembukaan file: baris-baris judul opsional, lalu baris kosong pemisah,
     * lalu header tabel. Semua baris di-offset satu kolom (kolom A kosong) supaya
     * konten mulai dari kolom B saat dibuka di Excel.
     */
    private function writePrelude($out, array $csvHeaders, array $prependRows): void
    {
        if (empty($prependRows)) {
            // Default: satu baris kosong agar header ada di baris 2.
            fputcsv($out, ['']);
        } else {
            foreach ($prependRows as $row) {
                // Pastikan kolom A selalu kosong (prepend '' jika belum).
                $first = $row[0] ?? '';
                $normalized = $first === '' ? $row : array_merge([''], $row);
                fputcsv($out, $normalized);
            }
            // Satu baris kosong pemisah antara judul dan tabel.
            fputcsv($out, ['']);
        }

        fputcsv($out, array_merge([''], $csvHeaders));
    }

    protected function buildReportFilename(string $slug, Request $request): string
    {
        $start = $request->input('start_date');
        $end   = $request->input('end_date');

        $suffix = match (true) {
            $start && $end => "{$start}_to_{$end}",
            (bool) $start  => "from-{$start}",
            (bool) $end    => "until-{$end}",
            default        => 'all',
        };

        return sprintf('%s_%s_%s.csv', $slug, $suffix, now()->format('YmdHis'));
    }
}
