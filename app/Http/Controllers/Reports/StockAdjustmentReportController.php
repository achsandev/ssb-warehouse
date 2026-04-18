<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\StreamsReportCsv;
use App\Http\Requests\Reports\FilterRequest;
use App\Models\StockAdjustmentDetail;
use App\Services\Reports\XlsxReportWriter;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockAdjustmentReportController extends Controller
{
    use ApiResponse, StreamsReportCsv;

    private const SLUG = 'stock-adjustment-report';

    private const DATE_COLUMN = 'wh_stock_adjustment.adjustment_date';

    private const HEADERS = [
        'Tanggal', 'Kode Barang', 'Nama Barang', 'Kuantitas', 'Nomor #',
        'Nama Departemen', 'Nama Proyek',
    ];

    public function index(FilterRequest $request): JsonResponse
    {
        $page    = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 10);

        $paginator = $this->buildQuery($request)
            ->orderByDesc('wh_stock_adjustment.adjustment_date')
            ->orderByDesc('wh_stock_adjustment.id')
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->successResponse(
            [
                'data' => $paginator->getCollection()->map(fn ($r) => $this->transform($r)),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ],
            'Stock adjustment report'
        );
    }

    /**
     * Export XLSX native (dengan merge cell + center align).
     * Mengikuti tampilan dan kolom export StockUsageReport.
     */
    public function export(FilterRequest $request): StreamedResponse
    {
        [$min, $max] = $this->rangeForTitle($request);
        $fmt = fn ($d) => $d ? Carbon::parse($d)->locale('id')->translatedFormat('d M Y') : '-';
        $periode = 'Dari ' . $fmt($min) . ' s/d ' . $fmt($max);

        $writer = new XlsxReportWriter('Penyesuaian Stok Barang');

        // Judul — merge B..H, center+middle.
        $writer
            ->addMergedTitle(row: 2, startCol: 'B', endCol: 'H', text: 'PT. SUMBER SETIA BUDI', style: 1)
            ->addMergedTitle(row: 3, startCol: 'B', endCol: 'H', text: 'Penyesuaian Stok Barang', style: 2)
            ->addMergedTitle(row: 4, startCol: 'B', endCol: 'H', text: $periode, style: 3);

        // Header tabel mulai dari B6 (row 5 sebagai spacer kosong).
        $headerRow = 6;
        $writer->setHeader($headerRow, 2, self::HEADERS);

        // Lebar kolom agar rapi (index 2..8 = B..H).
        $widths = [2 => 16, 3 => 16, 4 => 32, 5 => 12, 6 => 20, 7 => 22, 8 => 24];
        foreach ($widths as $idx => $w) {
            $writer->setColumnWidth($idx, $w);
        }

        // Data rows.
        $rowIdx = $headerRow + 1;
        foreach ($this->buildQuery($request)->orderBy('wh_stock_adjustment_detail.id')->cursor() as $r) {
            $d = $this->transform($r);

            $writer->addRow($rowIdx++, 2, [
                $d['adjustment_date'],
                $d['item_code'],
                $d['item_name'],
                $d['adjustment_qty'],
                $d['adjustment_number'],
                $d['department_name'],
                $d['project_name'],
            ]);
        }

        $filename = sprintf(
            '%s_%s_%s.xlsx',
            self::SLUG,
            $this->rangeFilenameSuffix($min, $max, $request),
            now()->format('YmdHis'),
        );

        return $writer->streamResponse($filename);
    }

    /** @return array{0:?string,1:?string} */
    private function rangeForTitle(FilterRequest $request): array
    {
        $q = (clone $this->buildQuery($request))->reorder()->toBase();
        $q->columns = null;

        $row = $q->selectRaw('MIN(wh_stock_adjustment.adjustment_date) as min_date, MAX(wh_stock_adjustment.adjustment_date) as max_date')
            ->first();

        return [
            $row?->min_date ?? $request->input('start_date'),
            $row?->max_date ?? $request->input('end_date'),
        ];
    }

    private function rangeFilenameSuffix(?string $min, ?string $max, FilterRequest $request): string
    {
        $start = $request->input('start_date') ?? $min;
        $end   = $request->input('end_date') ?? $max;

        return match (true) {
            $start && $end => "{$start}_to_{$end}",
            (bool) $start  => "from-{$start}",
            (bool) $end    => "until-{$end}",
            default        => 'all',
        };
    }

    private function buildQuery(FilterRequest $request): Builder
    {
        $query = StockAdjustmentDetail::query()
            ->from('wh_stock_adjustment_detail')
            ->join('wh_stock_adjustment', 'wh_stock_adjustment.id', '=', 'wh_stock_adjustment_detail.stock_adjustment_id')
            ->leftJoin('wh_items', 'wh_items.id', '=', 'wh_stock_adjustment_detail.item_id')
            ->select([
                'wh_stock_adjustment_detail.id as detail_id',
                'wh_stock_adjustment_detail.item_name',
                'wh_stock_adjustment_detail.unit_symbol',
                'wh_stock_adjustment_detail.warehouse_name',
                'wh_stock_adjustment_detail.rack_name',
                'wh_stock_adjustment_detail.adjustment_qty',
                'wh_stock_adjustment_detail.notes as detail_notes',
                'wh_stock_adjustment.adjustment_number',
                'wh_stock_adjustment.adjustment_date',
                'wh_stock_adjustment.stock_opname_number',
                'wh_stock_adjustment.notes',
                'wh_stock_adjustment.status',
                'wh_stock_adjustment.created_by_name',
                'wh_stock_adjustment.created_at',
                'wh_items.part_number as item_code',
            ]);

        $this->applyDateRange($query, self::DATE_COLUMN, $request);

        if ($status = $request->string('status')->toString()) {
            $query->where('wh_stock_adjustment.status', $status);
        }

        if ($search = $request->string('search')->toString()) {
            $like = "{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('wh_stock_adjustment.adjustment_number', 'like', $like)
                    ->orWhere('wh_stock_adjustment_detail.item_name', 'like', $like);
            });
        }

        return $query;
    }

    private function transform(object $row): array
    {
        return [
            'adjustment_number'   => $row->adjustment_number,
            'adjustment_date'     => $row->adjustment_date
                ? Carbon::parse($row->adjustment_date)->locale('id')->translatedFormat('d M Y')
                : '',
            'item_code'           => $row->item_code ?? '-',
            'item_name'           => $row->item_name ?? '-',
            'unit'                => $row->unit_symbol ?? '-',
            'warehouse_name'      => $row->warehouse_name ?? '-',
            'rack_name'           => $row->rack_name ?? '-',
            'adjustment_qty'      => (float) $row->adjustment_qty,
            'stock_opname_number' => $row->stock_opname_number ?? '-',
            'notes'               => $row->notes ?? $row->detail_notes ?? '',
            'status'              => $row->status,
            'created_by_name'     => $row->created_by_name ?? '-',
            'created_at'          => (string) $row->created_at,
            // Dua kolom di bawah sengaja dikosongkan — Stock Adjustment tidak memiliki
            // data departemen / proyek.
            'department_name'     => '',
            'project_name'        => '',
        ];
    }
}
