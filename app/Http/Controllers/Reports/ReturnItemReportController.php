<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\SelectsReportColumns;
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
    use ApiResponse, SelectsReportColumns, StreamsReportCsv;

    private const SLUG = 'return-item-report';

    private const DATE_COLUMN = 'wh_return_item.return_date';

    /**
     * Whitelist kolom export — key sesuai output `transform()`.
     * Key 'no' adalah counter row, tidak dari `transform()`.
     */
    private const COLUMN_DEFS = [
        'no'            => 'No',
        'return_date'   => 'Tanggal Return',
        'item_name'     => 'Nama Barang',
        'item_code'     => 'Kode Barang',
        'return_qty'    => 'Jumlah Return',
        'unit'          => 'Satuan',
        'description'   => 'Alasan Return',
        'supplier_name' => 'Nama Pemasok',
    ];

    private const COLUMN_WIDTHS = [
        'no'            => 6,
        'return_date'   => 18,
        'item_name'     => 32,
        'item_code'     => 16,
        'return_qty'    => 14,
        'unit'          => 10,
        'description'   => 36,
        'supplier_name' => 24,
    ];

    /** Kolom yang harus center-align (style 6). */
    private const CENTER_ALIGNED = ['no'];

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

        $writer = new XlsxReportWriter('Rincian Return Barang');

        $writer
            ->addMergedTitle(row: 2, startCol: 'B', endCol: $endColLetter, text: 'PT. SUMBER SETIA BUDI', style: 1)
            ->addMergedTitle(row: 3, startCol: 'B', endCol: $endColLetter, text: 'Rincian Return Barang', style: 2)
            ->addMergedTitle(row: 4, startCol: 'B', endCol: $endColLetter, text: $periode, style: 3);

        $headerRow = 6;
        $writer->setHeader($headerRow, 2, $headers);

        foreach ($columnKeys as $i => $key) {
            $colIdx = 2 + $i;
            $writer->setColumnWidth($colIdx, self::COLUMN_WIDTHS[$key] ?? 18);

            if (in_array($key, self::CENTER_ALIGNED, true)) {
                $writer->setColumnStyle($colIdx, 6);
            }
        }

        $rowIdx = $headerRow + 1;
        $no = 1;
        foreach ($this->buildQuery($request)->orderBy('wh_return_item_detail.id')->cursor() as $r) {
            $d = $this->transform($r);
            $rowNo = $no++;
            $values = $this->pickRow($d, $columnKeys, fn ($row, $k) => $k === 'no' ? $rowNo : ($row[$k] ?? ''));
            $writer->addRow($rowIdx++, 2, $values);
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
