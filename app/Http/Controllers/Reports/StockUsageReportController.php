<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\StreamsReportCsv;
use App\Http\Requests\Reports\FilterRequest;
use App\Models\ItemUsageDetail;
use App\Services\Reports\XlsxReportWriter;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockUsageReportController extends Controller
{
    use ApiResponse, StreamsReportCsv;

    private const SLUG = 'stock-usage-report';

    private const DATE_COLUMN = 'wh_item_usage.usage_date';

    private const HEADERS = [
        'Tanggal', 'Kode Barang', 'Nama Barang', 'Kuantitas', 'Nomor #',
        'Nama Departemen', 'Nama Proyek',
    ];

    public function index(FilterRequest $request): JsonResponse
    {
        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 10);

        $paginator = $this->buildQuery($request)
            ->orderByDesc('wh_item_usage.usage_date')
            ->orderByDesc('wh_item_usage.id')
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->successResponse(
            [
                'data' => $paginator->getCollection()->map(fn ($r) => $this->transform($r)),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
            'Stock usage report'
        );
    }

    /**
     * Export XLSX native (dengan merge cell + center align).
     * Tidak lagi memakai CSV karena CSV tidak support merge & alignment.
     */
    public function export(FilterRequest $request): StreamedResponse
    {
        [$min, $max] = $this->rangeForTitle($request);
        $fmt = fn ($d) => $d ? Carbon::parse($d)->locale('id')->translatedFormat('d M Y') : '-';
        $periode = 'Dari '.$fmt($min).' s/d '.$fmt($max);

        $writer = new XlsxReportWriter('Pemakaian Persediaan');

        // Judul — merge B..H, center+middle.
        $writer
            ->addMergedTitle(row: 2, startCol: 'B', endCol: 'H', text: 'PT. SUMBER SETIA BUDI', style: 1) // bold 14pt Arial
            ->addMergedTitle(row: 3, startCol: 'B', endCol: 'H', text: 'Pemakaian Persediaan', style: 2) // bold 20pt Arial
            ->addMergedTitle(row: 4, startCol: 'B', endCol: 'H', text: $periode, style: 3);            // 13pt Arial

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
        foreach ($this->buildQuery($request)->orderBy('wh_item_usage_detail.id')->cursor() as $r) {
            $d = $this->transform($r);

            $writer->addRow($rowIdx++, 2, [
                $d['usage_date'],
                $d['item_code'],
                $d['item_name'],
                $d['usage_qty'],
                $d['usage_number'],
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
        // clone & reset select agar tidak bentrok dengan only_full_group_by
        $q = (clone $this->buildQuery($request))->reorder()->toBase();
        $q->columns = null;

        $row = $q->selectRaw('MIN(wh_item_usage.usage_date) as min_date, MAX(wh_item_usage.usage_date) as max_date')
            ->first();

        return [
            $row?->min_date ?? $request->input('start_date'),
            $row?->max_date ?? $request->input('end_date'),
        ];
    }

    private function rangeFilenameSuffix(?string $min, ?string $max, FilterRequest $request): string
    {
        $start = $request->input('start_date') ?? $min;
        $end = $request->input('end_date') ?? $max;

        return match (true) {
            $start && $end => "{$start}_to_{$end}",
            (bool) $start => "from-{$start}",
            (bool) $end => "until-{$end}",
            default => 'all',
        };
    }

    private function buildQuery(FilterRequest $request): Builder
    {
        $query = ItemUsageDetail::query()
            ->from('wh_item_usage_detail')
            ->join('wh_item_usage', 'wh_item_usage.id', '=', 'wh_item_usage_detail.item_usage_id')
            ->leftJoin('wh_item_request', 'wh_item_request.id', '=', 'wh_item_usage.item_request_id')
            ->leftJoin('wh_items', 'wh_items.id', '=', 'wh_item_usage_detail.item_id')
            ->leftJoin('wh_item_units', 'wh_item_units.id', '=', 'wh_item_usage_detail.unit_id')
            ->select([
                'wh_item_usage_detail.id as detail_id',
                'wh_item_usage_detail.qty',
                'wh_item_usage_detail.usage_qty',
                'wh_item_usage_detail.description',
                'wh_item_usage.usage_number',
                'wh_item_usage.usage_date',
                'wh_item_usage.project_name',
                'wh_item_usage.status',
                'wh_item_usage.created_by_name',
                'wh_item_usage.created_at',
                'wh_item_request.department_name',
                'wh_items.name as item_name',
                'wh_items.part_number as item_code',
                'wh_item_units.name as unit_name',
                'wh_item_units.symbol as unit_symbol',
            ]);

        $this->applyDateRange($query, self::DATE_COLUMN, $request);

        if ($status = $request->string('status')->toString()) {
            $query->where('wh_item_usage.status', $status);
        }

        if ($search = $request->string('search')->toString()) {
            $like = "{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('wh_item_usage.usage_number', 'like', $like)
                    ->orWhere('wh_item_usage.project_name', 'like', $like)
                    ->orWhere('wh_items.name', 'like', $like);
            });
        }

        return $query;
    }

    private function transform(object $row): array
    {
        return [
            'usage_number' => $row->usage_number,
            // Format literal "01 Sep 2026" — XLSX menyimpan sebagai inline string.
            'usage_date' => $row->usage_date
                ? Carbon::parse($row->usage_date)->locale('id')->translatedFormat('d M Y')
                : '',
            'project_name' => $row->project_name ?? '-',
            'department_name' => $row->department_name ?? '-',
            'item_code' => $row->item_code ?? '-',
            'item_name' => $row->item_name ?? '-',
            'unit' => $row->unit_symbol ?: $row->unit_name ?: '-',
            'qty' => (float) $row->qty,
            'usage_qty' => (float) $row->usage_qty,
            'description' => $row->description ?? '',
            'status' => $row->status,
            'created_by_name' => $row->created_by_name ?? '-',
            'created_at' => (string) $row->created_at,
        ];
    }
}
