<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\StreamsReportCsv;
use App\Http\Requests\Reports\FilterRequest;
use App\Models\ReturnItemDetail;
use App\Services\Reports\XlsxReportWriter;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReturnItemReportController extends Controller
{
    use ApiResponse, StreamsReportCsv;

    private const SLUG = 'return-item-report';

    private const DATE_COLUMN = 'wh_return_item.return_date';

    private const HEADERS = [
        'No', 'Tanggal Return', 'Nama Barang', 'Kode Barang', 'Jumlah Return',
        'Satuan', 'Alasan Return', 'Nama Pemasok',
    ];

    public function index(FilterRequest $request): JsonResponse
    {
        $page    = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 10);

        $paginator = $this->buildQuery($request)
            ->orderByDesc('wh_return_item.return_date')
            ->orderByDesc('wh_return_item.id')
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
            'Return item report'
        );
    }

    /**
     * Export XLSX native (merge cell + center align).
     * Title 1, 2, 3 di-merge B:I, title 2 = "Rincian Return Barang".
     */
    public function export(FilterRequest $request): StreamedResponse
    {
        [$min, $max] = $this->rangeForTitle($request);
        $fmt = fn ($d) => $d ? Carbon::parse($d)->locale('id')->translatedFormat('d M Y') : '-';
        $periode = 'Dari ' . $fmt($min) . ' s/d ' . $fmt($max);

        $writer = new XlsxReportWriter('Rincian Return Barang');

        $writer
            ->addMergedTitle(row: 2, startCol: 'B', endCol: 'I', text: 'PT. SUMBER SETIA BUDI', style: 1)
            ->addMergedTitle(row: 3, startCol: 'B', endCol: 'I', text: 'Rincian Return Barang', style: 2)
            ->addMergedTitle(row: 4, startCol: 'B', endCol: 'I', text: $periode, style: 3);

        // Header tabel mulai dari B6 (row 5 sebagai spacer kosong).
        $headerRow = 6;
        $writer->setHeader($headerRow, 2, self::HEADERS);

        // Lebar kolom (idx 2..9 = B..I).
        $widths = [
            2 => 6,  // No
            3 => 18, // Tanggal Return
            4 => 32, // Nama Barang
            5 => 16, // Kode Barang
            6 => 14, // Jumlah Return
            7 => 10, // Satuan
            8 => 36, // Alasan Return
            9 => 24, // Nama Pemasok
        ];
        foreach ($widths as $idx => $w) {
            $writer->setColumnWidth($idx, $w);
        }

        // Kolom No (col B = 2) — align tengah.
        $writer->setColumnStyle(2, 6);

        // Data rows.
        $rowIdx = $headerRow + 1;
        $no = 1;
        foreach ($this->buildQuery($request)->orderBy('wh_return_item_detail.id')->cursor() as $r) {
            $d = $this->transform($r);

            $writer->addRow($rowIdx++, 2, [
                $no++,
                $d['return_date'],
                $d['item_name'],
                $d['item_code'],
                $d['return_qty'],
                $d['unit'],
                $d['description'],
                $d['supplier_name'],
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

        $row = $q->selectRaw('MIN(wh_return_item.return_date) as min_date, MAX(wh_return_item.return_date) as max_date')
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
        $query = ReturnItemDetail::query()
            ->from('wh_return_item_detail')
            ->join('wh_return_item', 'wh_return_item.id', '=', 'wh_return_item_detail.return_item_id')
            ->leftJoin('wh_purchase_order', 'wh_purchase_order.id', '=', 'wh_return_item.purchase_order_id')
            ->leftJoin('wh_items', 'wh_items.id', '=', 'wh_return_item_detail.item_id')
            ->leftJoin('wh_item_units', 'wh_item_units.id', '=', 'wh_return_item_detail.unit_id')
            // Supplier didapat dari PO detail yang item-nya sama dengan item return.
            ->leftJoin('wh_purchase_order_detail as pod', function ($j) {
                $j->on('pod.purchase_order_id', '=', 'wh_return_item.purchase_order_id')
                    ->on('pod.item_id', '=', 'wh_return_item_detail.item_id');
            })
            ->leftJoin('wh_supplier', 'wh_supplier.id', '=', 'pod.supplier_id')
            ->select([
                'wh_return_item_detail.id as detail_id',
                'wh_return_item_detail.qty',
                'wh_return_item_detail.return_qty',
                'wh_return_item_detail.description',
                'wh_return_item.return_number',
                'wh_return_item.return_date',
                'wh_return_item.project_name',
                'wh_return_item.status',
                'wh_return_item.created_by_name',
                'wh_return_item.created_at',
                'wh_purchase_order.po_number',
                'wh_items.name as item_name',
                'wh_items.part_number as item_code',
                'wh_item_units.name as unit_name',
                'wh_item_units.symbol as unit_symbol',
                'wh_supplier.name as supplier_name',
            ]);

        $this->applyDateRange($query, self::DATE_COLUMN, $request);

        if ($status = $request->string('status')->toString()) {
            $query->where('wh_return_item.status', $status);
        }

        if ($search = $request->string('search')->toString()) {
            $like = "{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('wh_return_item.return_number', 'like', $like)
                    ->orWhere('wh_return_item.project_name', 'like', $like)
                    ->orWhere('wh_items.name', 'like', $like);
            });
        }

        return $query;
    }

    private function transform(object $row): array
    {
        return [
            'return_number'   => $row->return_number,
            'return_date'     => $row->return_date
                ? Carbon::parse($row->return_date)->locale('id')->translatedFormat('d M Y')
                : '',
            'po_number'       => $row->po_number ?? '-',
            'project_name'    => $row->project_name ?? '-',
            'item_code'       => $row->item_code ?? '-',
            'item_name'       => $row->item_name ?? '-',
            'unit'            => $row->unit_symbol ?: $row->unit_name ?: '-',
            'supplier_name'   => $row->supplier_name ?? '-',
            'qty'             => (float) $row->qty,
            'return_qty'      => (float) $row->return_qty,
            'description'     => $row->description ?? '',
            'status'          => $row->status,
            'created_by_name' => $row->created_by_name ?? '-',
            'created_at'      => (string) $row->created_at,
        ];
    }
}
