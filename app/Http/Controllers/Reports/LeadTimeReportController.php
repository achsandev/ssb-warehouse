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
 * Lead Time Report — selisih hari antara tanggal PO dengan tanggal penerimaan pertama
 * per-item. Filter pada po_date.
 */
class LeadTimeReportController extends Controller
{
    use ApiResponse, StreamsReportCsv;

    private const SLUG = 'lead-time-report';

    private const HEADERS = [
        'No', 'Nama Barang', 'Kode Barang', 'Tanggal PO', 'Tanggal Diterima',
        'Lead Time (hari)', 'Keterangan',
    ];

    public function index(FilterRequest $request): JsonResponse
    {
        $page    = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 10);

        $query = $this->buildQuery($request);
        $total = (clone $query)->get()->count();

        $rows = $query
            ->orderByRaw('DATEDIFF(first_receipt_date, po_date) DESC')
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
            'Lead time report'
        );
    }

    /**
     * Export XLSX native (merge cell + center align).
     * Title 1, 2, 3 di-merge B:H, title 2 = "Rincian Lead Time".
     */
    public function export(FilterRequest $request): StreamedResponse
    {
        [$min, $max] = $this->rangeForTitle($request);
        $fmt = fn ($d) => $d ? Carbon::parse($d)->locale('id')->translatedFormat('d M Y') : '-';
        $periode = 'Dari ' . $fmt($min) . ' s/d ' . $fmt($max);

        $writer = new XlsxReportWriter('Rincian Lead Time');

        $writer
            ->addMergedTitle(row: 2, startCol: 'B', endCol: 'H', text: 'PT. SUMBER SETIA BUDI', style: 1)
            ->addMergedTitle(row: 3, startCol: 'B', endCol: 'H', text: 'Rincian Lead Time', style: 2)
            ->addMergedTitle(row: 4, startCol: 'B', endCol: 'H', text: $periode, style: 3);

        // Header tabel mulai dari B6 (row 5 sebagai spacer kosong).
        $headerRow = 6;
        $writer->setHeader($headerRow, 2, self::HEADERS);

        // Lebar kolom (idx 2..8 = B..H).
        $widths = [
            2 => 6,  // No
            3 => 32, // Nama Barang
            4 => 16, // Kode Barang
            5 => 16, // Tanggal PO
            6 => 18, // Tanggal Diterima
            7 => 16, // Lead Time (hari)
            8 => 28, // Keterangan
        ];
        foreach ($widths as $idx => $w) {
            $writer->setColumnWidth($idx, $w);
        }

        // Kolom No (B=2) & Lead Time (G=7) — align tengah.
        $writer->setColumnStyle(2, 6);
        $writer->setColumnStyle(7, 6);

        // Data rows.
        $rowIdx = $headerRow + 1;
        $no = 1;
        $fmtId = fn ($d) => $d ? Carbon::parse($d)->locale('id')->translatedFormat('d M Y') : '-';
        foreach (
            $this->buildQuery($request)
                ->orderBy('po.po_date')
                ->orderBy('pod.id')
                ->get() as $r
        ) {
            $d = $this->transform($r);

            $writer->addRow($rowIdx++, 2, [
                $no++,
                $d['item_name'],
                $d['item_code'],
                $fmtId($d['po_date']),
                $fmtId($d['first_receipt_date']),
                $d['lead_days'] ?? '-',
                '', // Keterangan — sengaja dikosongkan
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
        $row = DB::table('wh_purchase_order')
            ->selectRaw('MIN(po_date) as min_date, MAX(po_date) as max_date');

        if ($start = $request->input('start_date')) {
            $row->whereDate('po_date', '>=', $start);
        }
        if ($end = $request->input('end_date')) {
            $row->whereDate('po_date', '<=', $end);
        }

        $res = $row->first();

        return [
            $res?->min_date ?? $request->input('start_date'),
            $res?->max_date ?? $request->input('end_date'),
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

    /**
     * Query per-item (wh_purchase_order_detail), dengan MIN(receipt_date) untuk item
     * yang sama dari receipt milik PO yang sama (via subquery).
     */
    private function buildQuery(FilterRequest $request): Builder
    {
        $query = DB::table('wh_purchase_order_detail as pod')
            ->join('wh_purchase_order as po', 'po.id', '=', 'pod.purchase_order_id')
            ->leftJoin('wh_items as i', 'i.id', '=', 'pod.item_id')
            ->selectSub(function ($q) {
                $q->from('wh_receipt_item as ri')
                    ->join('wh_receipt_item_detail as rid', 'rid.receipt_item_id', '=', 'ri.id')
                    ->whereColumn('ri.purchase_order_id', 'po.id')
                    ->whereColumn('rid.item_id', 'pod.item_id')
                    ->selectRaw('MIN(ri.receipt_date)');
            }, 'first_receipt_date')
            ->addSelect([
                'pod.id as detail_id',
                'po.po_number',
                'po.po_date',
                'po.status',
                'po.created_by_name',
                'pod.item_id',
                'i.name as item_name',
                'i.part_number as item_code',
            ]);

        if ($start = $request->input('start_date')) {
            $query->whereDate('po.po_date', '>=', $start);
        }
        if ($end = $request->input('end_date')) {
            $query->whereDate('po.po_date', '<=', $end);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('po.status', $status);
        }

        if ($search = $request->string('search')->toString()) {
            $like = "{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('po.po_number', 'like', $like)
                    ->orWhere('i.name', 'like', $like)
                    ->orWhere('i.part_number', 'like', $like);
            });
        }

        return $query;
    }

    private function transform(object $row): array
    {
        $leadDays = null;
        if (! empty($row->first_receipt_date) && ! empty($row->po_date)) {
            $leadDays = Carbon::parse($row->po_date)->diffInDays(Carbon::parse($row->first_receipt_date));
        }

        return [
            'po_number'          => $row->po_number,
            // Tanggal dikembalikan mentah (YYYY-MM-DD atau null) agar FE bebas format.
            'po_date'            => $row->po_date ? Carbon::parse($row->po_date)->format('Y-m-d') : null,
            'first_receipt_date' => $row->first_receipt_date ? Carbon::parse($row->first_receipt_date)->format('Y-m-d') : null,
            'lead_days'          => $leadDays,
            'item_name'          => $row->item_name ?? '-',
            'item_code'          => $row->item_code ?? '-',
            'status'             => $row->status ?? null,
            'created_by_name'    => $row->created_by_name ?? '-',
        ];
    }
}
