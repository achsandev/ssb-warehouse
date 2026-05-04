<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\SelectsReportColumns;
use App\Http\Controllers\Reports\Concerns\StreamsReportCsv;
use App\Http\Requests\Reports\FilterRequest;
use App\Models\PurchaseOrderDetail;
use App\Services\Reports\XlsxReportWriter;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemPurchaseReportController extends Controller
{
    use ApiResponse, SelectsReportColumns, StreamsReportCsv;

    private const SLUG = 'item-purchase-report';

    private const DATE_COLUMN = 'wh_purchase_order.po_date';

    /** Whitelist kolom export — key sesuai output `transform()`. */
    private const COLUMN_DEFS = [
        'po_date'         => 'Tanggal',
        'po_number'       => 'Nomor #',
        'supplier_name'   => 'Pemasok',
        'item_code'       => 'Kode #',
        'item_name'       => 'Nama Barang',
        'qty'             => 'Kuantitas',
        'price'           => 'Harga',
        'total'           => 'Total Harga',
        'project_name'    => 'Nama Proyek',
        'department_name' => 'Nama Departemen',
    ];

    private const COLUMN_WIDTHS = [
        'po_date'         => 16,
        'po_number'       => 20,
        'supplier_name'   => 24,
        'item_code'       => 16,
        'item_name'       => 32,
        'qty'             => 12,
        'price'           => 16,
        'total'           => 18,
        'project_name'    => 22,
        'department_name' => 22,
    ];

    /** Kolom yang harus right-align (currency). */
    private const RIGHT_ALIGNED = ['price', 'total'];

    public function index(FilterRequest $request): JsonResponse
    {
        $page    = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 10);

        $paginator = $this->buildQuery($request)
            ->orderByDesc('wh_purchase_order.po_date')
            ->orderByDesc('wh_purchase_order.id')
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
            'Item purchase report'
        );
    }

    /**
     * Export XLSX native (merge cell + center align).
     * Mendukung pemilihan kolom dinamis via `columns[]` di request.
     */
    public function export(FilterRequest $request): StreamedResponse
    {
        [$min, $max] = $this->rangeForTitle($request);
        $fmt = fn ($d) => $d ? Carbon::parse($d)->locale('id')->translatedFormat('d M Y') : '-';
        $periode = 'Dari ' . $fmt($min) . ' s/d ' . $fmt($max);

        $columnKeys = $request->selectedColumns(array_keys(self::COLUMN_DEFS));
        $headers = $this->pickHeaders($columnKeys, self::COLUMN_DEFS);
        $endColLetter = $this->columnLetter(1 + count($columnKeys));

        $writer = new XlsxReportWriter('Rincian Pesanan Pembelian');

        $writer
            ->addMergedTitle(row: 2, startCol: 'B', endCol: $endColLetter, text: 'PT. SUMBER SETIA BUDI', style: 1)
            ->addMergedTitle(row: 3, startCol: 'B', endCol: $endColLetter, text: 'Rincian Pesanan Pembelian', style: 2)
            ->addMergedTitle(row: 4, startCol: 'B', endCol: $endColLetter, text: $periode, style: 3);

        $headerRow = 6;
        $writer->setHeader($headerRow, 2, $headers);

        foreach ($columnKeys as $i => $key) {
            $colIdx = 2 + $i;
            $writer->setColumnWidth($colIdx, self::COLUMN_WIDTHS[$key] ?? 18);

            // Right-align untuk kolom currency.
            if (in_array($key, self::RIGHT_ALIGNED, true)) {
                $writer->setColumnStyle($colIdx, 5);
            }
        }

        $rowIdx = $headerRow + 1;
        foreach ($this->buildQuery($request)->orderBy('wh_purchase_order_detail.id')->cursor() as $r) {
            $d = $this->transform($r);
            $writer->addRow($rowIdx++, 2, $this->pickRow($d, $columnKeys, fn ($row, $k) => $row[$k] ?? ''));
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

        $row = $q->selectRaw('MIN(wh_purchase_order.po_date) as min_date, MAX(wh_purchase_order.po_date) as max_date')
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
        $query = PurchaseOrderDetail::query()
            ->from('wh_purchase_order_detail')
            ->join('wh_purchase_order', 'wh_purchase_order.id', '=', 'wh_purchase_order_detail.purchase_order_id')
            ->leftJoin('wh_item_request', 'wh_item_request.id', '=', 'wh_purchase_order.item_request_id')
            ->leftJoin('wh_items', 'wh_items.id', '=', 'wh_purchase_order_detail.item_id')
            ->leftJoin('wh_item_units', 'wh_item_units.id', '=', 'wh_purchase_order_detail.unit_id')
            ->leftJoin('wh_supplier', 'wh_supplier.id', '=', 'wh_purchase_order_detail.supplier_id')
            ->select([
                'wh_purchase_order_detail.id as detail_id',
                'wh_purchase_order_detail.qty',
                'wh_purchase_order_detail.price',
                'wh_purchase_order_detail.total',
                'wh_purchase_order.po_number',
                'wh_purchase_order.po_date',
                'wh_purchase_order.status',
                'wh_purchase_order.created_by_name',
                'wh_purchase_order.created_at',
                'wh_item_request.project_name',
                'wh_item_request.department_name',
                'wh_items.name as item_name',
                'wh_items.part_number as item_code',
                'wh_item_units.name as unit_name',
                'wh_item_units.symbol as unit_symbol',
                'wh_supplier.name as supplier_name',
            ]);

        $this->applyDateRange($query, self::DATE_COLUMN, $request);

        if ($status = $request->string('status')->toString()) {
            $query->where('wh_purchase_order.status', $status);
        }

        if ($search = $request->string('search')->toString()) {
            $like = "{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('wh_purchase_order.po_number', 'like', $like)
                    ->orWhere('wh_items.name', 'like', $like)
                    ->orWhere('wh_supplier.name', 'like', $like);
            });
        }

        return $query;
    }

    /** Format angka ke Rupiah, contoh 100000 → "Rp. 100.000". */
    private function formatRupiah(float $value): string
    {
        return 'Rp. ' . number_format($value, 0, ',', '.');
    }

    private function transform(object $row): array
    {
        return [
            'po_number'       => $row->po_number,
            'po_date'         => $row->po_date
                ? Carbon::parse($row->po_date)->locale('id')->translatedFormat('d M Y')
                : '',
            'supplier_name'   => $row->supplier_name ?? '-',
            'item_code'       => $row->item_code ?? '-',
            'item_name'       => $row->item_name ?? '-',
            'unit'            => $row->unit_symbol ?: $row->unit_name ?: '-',
            'qty'             => (float) $row->qty,
            'price'           => $this->formatRupiah((float) $row->price),
            'total'           => $this->formatRupiah((float) $row->total),
            'project_name'    => $row->project_name ?? '-',
            'department_name' => $row->department_name ?? '-',
            'status'          => $row->status,
            'created_by_name' => $row->created_by_name ?? '-',
            'created_at'      => (string) $row->created_at,
        ];
    }
}
