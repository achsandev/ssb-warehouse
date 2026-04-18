<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\StreamsReportCsv;
use App\Http\Requests\Reports\FilterRequest;
use App\Traits\ApiResponse;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Life Time Report — umur persediaan sejak penerimaan pertama (receipt_date paling awal)
 * per item. `days_in_stock = DATEDIFF(NOW(), MIN(receipt_date))`.
 * Rentang tanggal difilter pada MIN(receipt_date).
 */
class LifeTimeReportController extends Controller
{
    use ApiResponse, StreamsReportCsv;

    private const SLUG = 'life-time-report';

    public function index(FilterRequest $request): JsonResponse
    {
        $page    = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 10);

        $query = $this->buildQuery($request);
        $total = (clone $query)->get()->count();

        $rows = $query
            ->orderByDesc('days_in_stock')
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
            'Life time report'
        );
    }

    public function export(FilterRequest $request): StreamedResponse
    {
        return $this->streamReportCsvFromCursor(
            query: $this->buildQuery($request)->orderByDesc('days_in_stock'),
            csvHeaders: ['Item Code', 'Item Name', 'Unit', 'First Receipt Date', 'Last Receipt Date', 'Days In Stock'],
            rowMapper: function ($row) {
                $d = $this->transform($row);
                return [
                    $d['item_code'], $d['item_name'], $d['unit'],
                    $d['first_receipt_date'], $d['last_receipt_date'], $d['days_in_stock'],
                ];
            },
            slug: self::SLUG,
            request: $request,
        );
    }

    private function buildQuery(FilterRequest $request): Builder
    {
        $query = DB::table('wh_receipt_item_detail as d')
            ->join('wh_receipt_item as r', 'r.id', '=', 'd.receipt_item_id')
            ->leftJoin('wh_items as i', 'i.id', '=', 'd.item_id')
            ->leftJoin('wh_item_units as u', 'u.id', '=', 'd.unit_id')
            ->where('r.status', 'Approved')
            ->groupBy('d.item_id', 'i.part_number', 'i.name', 'u.name', 'u.symbol')
            ->select([
                'd.item_id',
                'i.part_number as item_code',
                'i.name as item_name',
                'u.name as unit_name',
                'u.symbol as unit_symbol',
                DB::raw('MIN(r.receipt_date) as first_receipt_date'),
                DB::raw('MAX(r.receipt_date) as last_receipt_date'),
                DB::raw('DATEDIFF(CURRENT_DATE, MIN(r.receipt_date)) as days_in_stock'),
            ]);

        // Filter pada tanggal penerimaan pertama (HAVING agar jalan setelah GROUP BY)
        if ($start = $request->input('start_date')) {
            $query->having('first_receipt_date', '>=', $start);
        }
        if ($end = $request->input('end_date')) {
            $query->having('first_receipt_date', '<=', $end);
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
            'item_code'          => $row->item_code ?? '-',
            'item_name'          => $row->item_name ?? '-',
            'unit'               => $row->unit_symbol ?: $row->unit_name ?: '-',
            'first_receipt_date' => $row->first_receipt_date ? Carbon::parse($row->first_receipt_date)->format('Y-m-d') : null,
            'last_receipt_date'  => $row->last_receipt_date ? Carbon::parse($row->last_receipt_date)->format('Y-m-d') : null,
            'days_in_stock'      => (int) $row->days_in_stock,
        ];
    }
}
