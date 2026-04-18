<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\StreamsReportCsv;
use App\Http\Requests\Reports\FilterRequest;
use App\Models\ReceiveItemDetail;
use App\Services\Reports\XlsxReportWriter;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemReceiptReportController extends Controller
{
    use ApiResponse, StreamsReportCsv;

    private const SLUG = 'item-receipt-report';

    private const DATE_COLUMN = 'wh_receipt_item.receipt_date';

    private const HEADERS = [
        'Kode #', 'Nama Barang', 'Kuantitas', 'Satuan', 'Nomor #',
        'Tanggal', 'Pemasok', 'Kuantitas Dipesan', 'Total Harga',
    ];

    public function index(FilterRequest $request): JsonResponse
    {
        $page    = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 10);

        $paginator = $this->buildQuery($request)
            ->orderByDesc('wh_receipt_item.receipt_date')
            ->orderByDesc('wh_receipt_item.id')
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
            'Item receipt report'
        );
    }

    /**
     * Export XLSX native (merge cell + center align).
     * Title 1, 2, 3 di-merge B:J, title 2 = "Rincian Penerimaan Barang".
     */
    public function export(FilterRequest $request): StreamedResponse
    {
        [$min, $max] = $this->rangeForTitle($request);
        $fmt = fn ($d) => $d ? Carbon::parse($d)->locale('id')->translatedFormat('d M Y') : '-';
        $periode = 'Dari ' . $fmt($min) . ' s/d ' . $fmt($max);

        $writer = new XlsxReportWriter('Rincian Penerimaan Barang');

        $writer
            ->addMergedTitle(row: 2, startCol: 'B', endCol: 'J', text: 'PT. SUMBER SETIA BUDI', style: 1)
            ->addMergedTitle(row: 3, startCol: 'B', endCol: 'J', text: 'Rincian Penerimaan Barang', style: 2)
            ->addMergedTitle(row: 4, startCol: 'B', endCol: 'J', text: $periode, style: 3);

        // Header tabel mulai dari B6 (row 5 sebagai spacer kosong).
        $headerRow = 6;
        $writer->setHeader($headerRow, 2, self::HEADERS);

        // Lebar kolom (idx 2..10 = B..J).
        $widths = [
            2  => 16, // Kode #
            3  => 32, // Nama Barang
            4  => 12, // Kuantitas
            5  => 10, // Satuan
            6  => 22, // Nomor #
            7  => 16, // Tanggal
            8  => 24, // Pemasok
            9  => 18, // Kuantitas Dipesan
            10 => 20, // Total Harga
        ];
        foreach ($widths as $idx => $w) {
            $writer->setColumnWidth($idx, $w);
        }

        // Total Harga (col J = 10) — align kanan.
        $writer->setColumnStyle(10, 5);

        // Data rows.
        $rowIdx = $headerRow + 1;
        foreach ($this->buildQuery($request)->orderBy('wh_receipt_item_detail.id')->cursor() as $r) {
            $d = $this->transform($r);

            $writer->addRow($rowIdx++, 2, [
                $d['item_code'],
                $d['item_name'],
                $d['qty_received'],
                $d['unit'],
                $d['receipt_number'],
                $d['receipt_date'],
                $d['supplier_name'],
                $d['qty'],
                $d['total'],
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

        $row = $q->selectRaw('MIN(wh_receipt_item.receipt_date) as min_date, MAX(wh_receipt_item.receipt_date) as max_date')
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
        $query = ReceiveItemDetail::query()
            ->from('wh_receipt_item_detail')
            ->join('wh_receipt_item', 'wh_receipt_item.id', '=', 'wh_receipt_item_detail.receipt_item_id')
            ->leftJoin('wh_purchase_order', 'wh_purchase_order.id', '=', 'wh_receipt_item.purchase_order_id')
            ->leftJoin('wh_items', 'wh_items.id', '=', 'wh_receipt_item_detail.item_id')
            ->leftJoin('wh_item_units', 'wh_item_units.id', '=', 'wh_receipt_item_detail.unit_id')
            ->leftJoin('wh_supplier', 'wh_supplier.id', '=', 'wh_receipt_item_detail.supplier_id')
            ->select([
                'wh_receipt_item_detail.id as detail_id',
                'wh_receipt_item_detail.qty',
                'wh_receipt_item_detail.qty_received',
                'wh_receipt_item_detail.price',
                'wh_receipt_item_detail.total',
                'wh_receipt_item.receipt_number',
                'wh_receipt_item.receipt_date',
                'wh_receipt_item.status',
                'wh_receipt_item.created_by_name',
                'wh_receipt_item.created_at',
                'wh_purchase_order.po_number',
                'wh_items.name as item_name',
                'wh_items.part_number as item_code',
                'wh_item_units.name as unit_name',
                'wh_item_units.symbol as unit_symbol',
                'wh_supplier.name as supplier_name',
            ]);

        $this->applyDateRange($query, self::DATE_COLUMN, $request);

        if ($status = $request->string('status')->toString()) {
            $query->where('wh_receipt_item.status', $status);
        }

        if ($search = $request->string('search')->toString()) {
            $like = "{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('wh_receipt_item.receipt_number', 'like', $like)
                    ->orWhere('wh_purchase_order.po_number', 'like', $like)
                    ->orWhere('wh_items.name', 'like', $like);
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
            'receipt_number'  => $row->receipt_number,
            'receipt_date'    => $row->receipt_date
                ? Carbon::parse($row->receipt_date)->locale('id')->translatedFormat('d M Y')
                : '',
            'po_number'       => $row->po_number ?? '-',
            'item_code'       => $row->item_code ?? '-',
            'item_name'       => $row->item_name ?? '-',
            'unit'            => $row->unit_symbol ?: $row->unit_name ?: '-',
            'supplier_name'   => $row->supplier_name ?? '-',
            'qty'             => (float) $row->qty,
            'qty_received'    => (float) $row->qty_received,
            'price'           => $this->formatRupiah((float) $row->price),
            'total'           => $this->formatRupiah((float) $row->total),
            'status'          => $row->status,
            'created_by_name' => $row->created_by_name ?? '-',
            'created_at'      => (string) $row->created_at,
        ];
    }
}
