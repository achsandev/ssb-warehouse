<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
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
    use ApiResponse, StreamsReportCsv;

    private const SLUG = 'item-purchase-report';

    private const DATE_COLUMN = 'wh_purchase_order.po_date';

    private const HEADERS = [
        'Tanggal', 'Nomor #', 'Pemasok', 'Kode #', 'Nama Barang',
        'Kuantitas', 'Harga', 'Total Harga', 'Nama Proyek', 'Nama Departemen',
    ];

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
     * Title 1, 2, 3 di-merge B:K, dengan title 2 = "Rincian Pesanan Pembelian".
     */
    public function export(FilterRequest $request): StreamedResponse
    {
        [$min, $max] = $this->rangeForTitle($request);
        $fmt = fn ($d) => $d ? Carbon::parse($d)->locale('id')->translatedFormat('d M Y') : '-';
        $periode = 'Dari ' . $fmt($min) . ' s/d ' . $fmt($max);

        $writer = new XlsxReportWriter('Rincian Pesanan Pembelian');

        // Judul — merge B..T, center+middle.
        $writer
            ->addMergedTitle(row: 2, startCol: 'B', endCol: 'K', text: 'PT. SUMBER SETIA BUDI', style: 1)
            ->addMergedTitle(row: 3, startCol: 'B', endCol: 'K', text: 'Rincian Pesanan Pembelian', style: 2)
            ->addMergedTitle(row: 4, startCol: 'B', endCol: 'K', text: $periode, style: 3);

        // Header tabel mulai dari B6 (row 5 sebagai spacer kosong).
        $headerRow = 6;
        $writer->setHeader($headerRow, 2, self::HEADERS);

        // Lebar kolom (index 2..11 = B..K) agar rapi.
        $widths = [
            2  => 16, // Tanggal
            3  => 20, // Nomor #
            4  => 24, // Pemasok
            5  => 16, // Kode #
            6  => 32, // Nama Barang
            7  => 12, // Kuantitas
            8  => 16, // Harga
            9  => 18, // Total Harga
            10 => 22, // Nama Proyek
            11 => 22, // Nama Departemen
        ];
        foreach ($widths as $idx => $w) {
            $writer->setColumnWidth($idx, $w);
        }

        // Harga (col H = 8) & Total Harga (col I = 9) — align kanan.
        $writer->setColumnStyle(8, 5);
        $writer->setColumnStyle(9, 5);

        // Data rows.
        $rowIdx = $headerRow + 1;
        foreach ($this->buildQuery($request)->orderBy('wh_purchase_order_detail.id')->cursor() as $r) {
            $d = $this->transform($r);

            $writer->addRow($rowIdx++, 2, [
                $d['po_date'],
                $d['po_number'],
                $d['supplier_name'],
                $d['item_code'],
                $d['item_name'],
                $d['qty'],
                $d['price'],
                $d['total'],
                $d['project_name'],
                $d['department_name'],
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
