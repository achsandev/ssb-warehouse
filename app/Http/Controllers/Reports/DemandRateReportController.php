<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\StreamsReportCsv;
use App\Http\Requests\Reports\FilterRequest;
use App\Services\Reports\XlsxReportWriter;
use App\Traits\ApiResponse;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Demand Rate Report — frekuensi permintaan per item per bulan (pivot 12 bulan).
 * Layout export mengikuti format "Inventory Forecast Planning".
 */
class DemandRateReportController extends Controller
{
    use ApiResponse, StreamsReportCsv;

    private const SLUG = 'demand-rate-report';

    public function index(FilterRequest $request): JsonResponse
    {
        $page    = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 10);

        $query = $this->buildQuery($request);
        $total = (clone $query)->get()->count();

        $rows = $query
            ->orderByDesc('total_requests')
            ->forPage($page, $perPage)
            ->get();

        return $this->successResponse(
            [
                'data' => $rows->map(fn ($r) => $this->transform($r)),
                'meta' => [
                    'current_page' => $page,
                    'per_page'     => $perPage,
                    'total'        => $total,
                    'last_page'    => max(1, (int) ceil($total / $perPage)),
                ],
            ],
            'Demand rate report'
        );
    }

    /**
     * Export XLSX pivot 12 bulan:
     *  Part System | Desc' | Part Cat'g | Call&Demand (12 bulan) | Grand Total
     *  | Rata2 consumption | Call | Moving Category | Min | Max | SS
     */
    public function export(FilterRequest $request): StreamedResponse
    {
        [$rangeStart, $rangeEnd] = $this->resolveMonthRange($request);
        $months = $this->buildMonths($rangeStart, $rangeEnd);

        $pivot = $this->buildPivot($rangeStart, $rangeEnd, $months);

        $writer = new XlsxReportWriter('Demand Rate');

        $firstLabel = $months[0]['label'] ?? '-';
        $lastLabel  = end($months)['label'] ?? '-';

        // Baris catatan di atas tabel (style 7 = italic 10pt).
        $writer->addCell(1, 1, '= all Hystorical Transaction, ' . $firstLabel . ' to ' . $lastLabel, 7);
        $writer->addCell(2, 1, 'For Inventory Forecast Planning RM Available Parts Scheduling', 7);

        // Layout kolom:
        //   A = Part System
        //   B = Desc'
        //   C = Part Cat'g
        //   D..(D+n-1) = bulan-bulan
        //   lalu Grand Total, Rata2 consumption, Call, Moving Category, Min, Max, SS
        $monthCount   = count($months);
        $monthStartCol = 4;                       // D
        $monthEndIdx  = $monthStartCol + $monthCount - 1;
        $monthStartLetter = $this->colLetter($monthStartCol);
        $monthEndLetter   = $this->colLetter($monthEndIdx);

        $grandIdx   = $monthEndIdx + 1;
        $rataIdx    = $grandIdx + 1;
        $callIdx    = $rataIdx + 1;
        $movingIdx  = $callIdx + 1;
        $minIdx     = $movingIdx + 1;
        $maxIdx     = $minIdx + 1;
        $ssIdx      = $maxIdx + 1;

        // Header 2 baris (row 3 & 4). Fixed columns di-merge vertikal, "Call&Demand" horizontal.
        $headerTop    = 3;
        $headerBottom = 4;

        $writer->addMerge($headerTop, $headerBottom, 'A', 'A', 'Part System', 4);
        $writer->addMerge($headerTop, $headerBottom, 'B', 'B', "Desc'", 4);
        $writer->addMerge($headerTop, $headerBottom, 'C', 'C', "Part Cat'g", 4);

        $writer->addMerge($headerTop, $headerTop, $monthStartLetter, $monthEndLetter, 'Call&Demand', 4);
        foreach ($months as $i => $m) {
            $writer->addCell($headerBottom, $monthStartCol + $i, $m['label'], 4);
        }

        $writer->addMerge($headerTop, $headerBottom, $this->colLetter($grandIdx),  $this->colLetter($grandIdx),  'Grand Total',        4);
        $writer->addMerge($headerTop, $headerBottom, $this->colLetter($rataIdx),   $this->colLetter($rataIdx),   'Rata2 consumption',  4);
        $writer->addMerge($headerTop, $headerBottom, $this->colLetter($callIdx),   $this->colLetter($callIdx),   'Call',               4);
        $writer->addMerge($headerTop, $headerBottom, $this->colLetter($movingIdx), $this->colLetter($movingIdx), 'Moving Category',    4);
        $writer->addMerge($headerTop, $headerBottom, $this->colLetter($minIdx),    $this->colLetter($minIdx),    'Min',                4);
        $writer->addMerge($headerTop, $headerBottom, $this->colLetter($maxIdx),    $this->colLetter($maxIdx),    'Max',                4);
        $writer->addMerge($headerTop, $headerBottom, $this->colLetter($ssIdx),     $this->colLetter($ssIdx),     'SS',                 4);

        // Lebar kolom.
        $writer->setColumnWidth(1, 14); // Part System
        $writer->setColumnWidth(2, 36); // Desc'
        $writer->setColumnWidth(3, 14); // Part Cat'g
        for ($i = 0; $i < $monthCount; $i++) {
            $writer->setColumnWidth($monthStartCol + $i, 9);
        }
        $writer->setColumnWidth($grandIdx,  12);
        $writer->setColumnWidth($rataIdx,   14);
        $writer->setColumnWidth($callIdx,   8);
        $writer->setColumnWidth($movingIdx, 18);
        $writer->setColumnWidth($minIdx,    8);
        $writer->setColumnWidth($maxIdx,    8);
        $writer->setColumnWidth($ssIdx,     8);

        // Data rows mulai row 5.
        $rowIdx = $headerBottom + 1;
        foreach ($pivot as $p) {
            $monthlyValues = [];
            $total    = 0;
            $callCnt  = 0;

            foreach ($months as $m) {
                $cnt = $p['months'][$m['key']] ?? 0;
                $monthlyValues[] = $cnt;
                $total += $cnt;
                if ($cnt > 0) {
                    $callCnt++;
                }
            }

            $nonZero = array_filter($monthlyValues, fn ($v) => $v > 0);
            $min    = $nonZero !== [] ? min($nonZero) : 0;
            $max    = $monthlyValues !== [] ? max($monthlyValues) : 0;
            $rata   = $monthCount > 0 ? round($total / $monthCount, 2) : 0;
            $moving = $callCnt >= 6 ? 'Fast Moving' : 'Slow Moving';
            $ss     = $max * 2; // placeholder safety-stock

            // Fixed kolom.
            $writer->addCell($rowIdx, 1, $p['part_number'] ?? '-', 0);
            $writer->addCell($rowIdx, 2, $p['desc'] ?? '-', 0);
            $writer->addCell($rowIdx, 3, $p['part_cat'] ?? '-', 0);

            // Monthly counts — tampilkan kosong untuk 0 agar rapi mirip screenshot.
            foreach ($monthlyValues as $i => $v) {
                $writer->addCell($rowIdx, $monthStartCol + $i, $v > 0 ? $v : '', 6);
            }

            $writer->addCell($rowIdx, $grandIdx,  $total, 6);
            $writer->addCell($rowIdx, $rataIdx,   $rata, 6);
            $writer->addCell($rowIdx, $callIdx,   $callCnt, 6);
            $writer->addCell($rowIdx, $movingIdx, $moving, 8); // red bold
            $writer->addCell($rowIdx, $minIdx,    $min, 6);
            $writer->addCell($rowIdx, $maxIdx,    $max, 6);
            $writer->addCell($rowIdx, $ssIdx,     $ss, 6);

            $rowIdx++;
        }

        $filename = sprintf(
            '%s_%s_%s.xlsx',
            self::SLUG,
            $rangeStart->format('Y-m') . '_to_' . $rangeEnd->format('Y-m'),
            now()->format('YmdHis'),
        );

        return $writer->streamResponse($filename);
    }

    /** Resolve rentang bulan: dari filter, atau tahun berjalan Jan..Dec. */
    private function resolveMonthRange(FilterRequest $request): array
    {
        $start = $request->input('start_date');
        $end   = $request->input('end_date');

        if ($start && $end) {
            return [Carbon::parse($start)->startOfMonth(), Carbon::parse($end)->endOfMonth()];
        }

        // Default: tahun berjalan, Jan..Des.
        $year = (int) date('Y');

        return [
            Carbon::create($year, 1, 1)->startOfMonth(),
            Carbon::create($year, 12, 31)->endOfMonth(),
        ];
    }

    /**
     * Bangun daftar bulan [['key'=>'YYYY-MM','label'=>'Jan-24'], ...].
     * Dibatasi 24 bulan untuk mencegah dataset terlalu lebar.
     */
    private function buildMonths(Carbon $start, Carbon $end): array
    {
        $months = [];
        $cur    = $start->copy()->startOfMonth();
        $max    = 24;

        while ($cur->lessThanOrEqualTo($end) && count($months) < $max) {
            $months[] = [
                'key'   => $cur->format('Y-m'),
                'label' => $cur->locale('id')->translatedFormat('M-y'),
            ];
            $cur->addMonth();
        }

        return $months;
    }

    /**
     * Pivot per item: count request per bulan.
     *
     * @return array<int, array{part_number:?string,desc:?string,part_cat:?string,months:array<string,int>}>
     */
    private function buildPivot(Carbon $start, Carbon $end, array $months): array
    {
        $rows = DB::table('wh_item_request_detail as d')
            ->join('wh_item_request as r', 'r.id', '=', 'd.item_request_id')
            ->leftJoin('wh_items as i', 'i.id', '=', 'd.item_id')
            ->whereBetween('r.request_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('i.id', 'i.part_number', 'i.name', 'i.item_category_name', 'ym')
            ->select([
                'i.id as item_id',
                'i.part_number',
                'i.name as desc',
                'i.item_category_name as part_cat',
                DB::raw("DATE_FORMAT(r.request_date, '%Y-%m') as ym"),
                DB::raw('COUNT(d.id) as month_count'),
            ])
            ->get();

        $pivot = [];
        foreach ($rows as $r) {
            $id = $r->item_id;
            if (! isset($pivot[$id])) {
                $pivot[$id] = [
                    'part_number' => $r->part_number,
                    'desc'        => $r->desc,
                    'part_cat'    => $r->part_cat,
                    'months'      => array_fill_keys(array_column($months, 'key'), 0),
                ];
            }

            if (array_key_exists($r->ym, $pivot[$id]['months'])) {
                $pivot[$id]['months'][$r->ym] = (int) $r->month_count;
            }
        }

        // Urutkan berdasarkan part_number agar konsisten.
        uasort($pivot, fn ($a, $b) => strcmp((string) $a['part_number'], (string) $b['part_number']));

        return $pivot;
    }

    /** Ubah index 1-based ke huruf kolom. 1 => "A", 27 => "AA". */
    private function colLetter(int $idx): string
    {
        $letter = '';
        while ($idx > 0) {
            $mod    = ($idx - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $idx    = intdiv($idx - 1, 26);
        }

        return $letter;
    }

    // ─── Preview list (tabel di FE) ──────────────────────────────────────────
    private function buildQuery(FilterRequest $request): Builder
    {
        $query = DB::table('wh_item_request_detail as d')
            ->join('wh_item_request as r', 'r.id', '=', 'd.item_request_id')
            ->leftJoin('wh_items as i', 'i.id', '=', 'd.item_id')
            ->leftJoin('wh_item_units as u', 'u.id', '=', 'd.unit_id')
            ->groupBy('d.item_id', 'i.part_number', 'i.name', 'u.name', 'u.symbol')
            ->select([
                'd.item_id',
                'i.part_number',
                'i.name as item_name',
                'u.name as unit_name',
                'u.symbol as unit_symbol',
                DB::raw('COUNT(d.id) as total_requests'),
                DB::raw('COUNT(DISTINCT d.item_request_id) as distinct_requests'),
                DB::raw('COALESCE(SUM(d.qty), 0) as total_qty'),
            ]);

        if ($start = $request->input('start_date')) {
            $query->whereDate('r.request_date', '>=', $start);
        }
        if ($end = $request->input('end_date')) {
            $query->whereDate('r.request_date', '<=', $end);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('r.status', $status);
        }

        if ($search = $request->string('search')->toString()) {
            $like = "{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('i.name', 'like', $like)
                    ->orWhere('i.part_number', 'like', $like);
            });
        }

        return $query;
    }

    private function transform(object $row): array
    {
        return [
            'part_number'       => $row->part_number ?? '-',
            'item_name'         => $row->item_name ?? '-',
            'unit'              => $row->unit_symbol ?: $row->unit_name ?: '-',
            'total_requests'    => (int) $row->total_requests,
            'distinct_requests' => (int) $row->distinct_requests,
            'total_qty'         => (float) $row->total_qty,
        ];
    }
}
