<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Agregasi data untuk Dashboard Warehouse.
 *
 * Endpoint ini meringkas banyak tabel dalam satu response agar hemat roundtrip.
 * Tiap query dibatasi & di-index-friendly. Kolom/kondisi konservatif — jika
 * tabel kosong, hasilnya array kosong (tidak melempar error).
 */
class DashboardController extends Controller
{
    use ApiResponse;

    public function summary(Request $request): JsonResponse
    {
        $now        = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $yearStart  = $now->copy()->startOfYear();
        $rangeStart = $now->copy()->subMonths(11)->startOfMonth();

        return $this->successResponse(
            [
                'kpis'                => $this->kpis($now, $monthStart, $yearStart),
                'stock_movement'      => $this->stockMovement($rangeStart, $now),
                'category_distribution' => $this->categoryDistribution(),
                'top_requested_items' => $this->topRequestedItems($rangeStart, $now),
                'low_stock'           => $this->lowStock(),
                'recent_activity'     => $this->recentActivity(),
                'pending_approvals'   => $this->pendingApprovals(),
            ],
            'Dashboard summary'
        );
    }

    /** @return array<string, int> */
    private function kpis(Carbon $now, Carbon $monthStart, Carbon $yearStart): array
    {
        return [
            'total_items'              => (int) DB::table('wh_items')->count(),
            'total_suppliers'          => (int) DB::table('wh_supplier')->count(),
            'total_warehouses'         => (int) DB::table('wh_warehouse')->count(),
            'po_this_month'            => (int) DB::table('wh_purchase_order')
                ->whereBetween('po_date', [$monthStart->toDateString(), $now->toDateString()])
                ->count(),
            'receipt_this_month'       => (int) DB::table('wh_receipt_item')
                ->whereBetween('receipt_date', [$monthStart->toDateString(), $now->toDateString()])
                ->count(),
            'usage_this_month'         => (int) DB::table('wh_item_usage')
                ->whereBetween('usage_date', [$monthStart->toDateString(), $now->toDateString()])
                ->count(),
            'pending_po_approval'      => (int) DB::table('wh_purchase_order')
                ->where('status', 'Waiting Approval')
                ->count(),
            'pending_receipt_approval' => (int) DB::table('wh_receipt_item')
                ->where('status', 'Waiting Approval')
                ->count(),
            'pending_request_approval' => (int) DB::table('wh_item_request')
                ->where('status', 'Waiting Approval')
                ->count(),
            'po_this_year'             => (int) DB::table('wh_purchase_order')
                ->whereBetween('po_date', [$yearStart->toDateString(), $now->toDateString()])
                ->count(),
        ];
    }

    /**
     * Pergerakan stok 12 bulan terakhir: count Receive vs count Item Usage per bulan.
     *
     * @return array<int, array{month:string,receive:int,usage:int}>
     */
    private function stockMovement(Carbon $from, Carbon $now): array
    {
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $m        = $from->copy()->addMonths($i);
            $months[] = [
                'key'   => $m->format('Y-m'),
                'label' => $m->locale('id')->translatedFormat('M Y'),
            ];
        }

        $receiveMap = DB::table('wh_receipt_item')
            ->selectRaw("DATE_FORMAT(receipt_date, '%Y-%m') as ym, COUNT(*) as total")
            ->whereBetween('receipt_date', [$from->toDateString(), $now->toDateString()])
            ->groupBy('ym')
            ->pluck('total', 'ym')
            ->toArray();

        $usageMap = DB::table('wh_item_usage')
            ->selectRaw("DATE_FORMAT(usage_date, '%Y-%m') as ym, COUNT(*) as total")
            ->whereBetween('usage_date', [$from->toDateString(), $now->toDateString()])
            ->groupBy('ym')
            ->pluck('total', 'ym')
            ->toArray();

        return array_map(
            fn ($m) => [
                'month'   => $m['label'],
                'receive' => (int) ($receiveMap[$m['key']] ?? 0),
                'usage'   => (int) ($usageMap[$m['key']] ?? 0),
            ],
            $months
        );
    }

    /**
     * Distribusi item per kategori (Top 8).
     *
     * @return array<int, array{name:string,total:int}>
     */
    private function categoryDistribution(): array
    {
        $rows = DB::table('wh_items')
            ->selectRaw('COALESCE(item_category_name, "Tanpa Kategori") as name, COUNT(*) as total')
            ->groupBy('item_category_name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return $rows->map(fn ($r) => [
            'name'  => (string) $r->name,
            'total' => (int) $r->total,
        ])->all();
    }

    /**
     * Top 7 item yang paling sering direquest (12 bulan terakhir).
     *
     * @return array<int, array{name:string,total:int}>
     */
    private function topRequestedItems(Carbon $from, Carbon $now): array
    {
        $rows = DB::table('wh_item_request_detail as d')
            ->join('wh_item_request as r', 'r.id', '=', 'd.item_request_id')
            ->leftJoin('wh_items as i', 'i.id', '=', 'd.item_id')
            ->selectRaw('COALESCE(i.name, "Unknown") as name, COUNT(d.id) as total')
            ->whereBetween('r.request_date', [$from->toDateString(), $now->toDateString()])
            ->groupBy('i.id', 'i.name')
            ->orderByDesc('total')
            ->limit(7)
            ->get();

        return $rows->map(fn ($r) => [
            'name'  => (string) $r->name,
            'total' => (int) $r->total,
        ])->all();
    }

    /**
     * Barang dengan stok di bawah min_qty.
     *
     * @return array<int, array{name:string,min_qty:int,current_stock:float,pct:float}>
     */
    private function lowStock(): array
    {
        $rows = DB::table('wh_items as i')
            ->leftJoin('wh_stocks as s', 's.item_id', '=', 'i.id')
            ->selectRaw('i.id, i.name, COALESCE(i.min_qty, 0) as min_qty, COALESCE(SUM(s.qty), 0) as current_stock')
            ->groupBy('i.id', 'i.name', 'i.min_qty')
            ->havingRaw('COALESCE(i.min_qty, 0) > 0 AND COALESCE(SUM(s.qty), 0) < COALESCE(i.min_qty, 0)')
            ->orderByRaw('(COALESCE(SUM(s.qty), 0) / NULLIF(COALESCE(i.min_qty, 0), 0)) ASC')
            ->limit(10)
            ->get();

        return $rows->map(function ($r) {
            $pct = $r->min_qty > 0 ? round(((float) $r->current_stock / (float) $r->min_qty) * 100, 1) : 0;

            return [
                'name'          => (string) $r->name,
                'min_qty'       => (int) $r->min_qty,
                'current_stock' => (float) $r->current_stock,
                'pct'           => $pct,
            ];
        })->all();
    }

    /**
     * 10 aktivitas terbaru dari 3 sumber (receipt, usage, item request) digabung.
     *
     * @return array<int, array<string,mixed>>
     */
    private function recentActivity(): array
    {
        $receipts = DB::table('wh_receipt_item')
            ->select('receipt_number as number', 'created_by_name as user', 'status', 'created_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'type'   => 'receipt',
                'number' => $r->number,
                'user'   => $r->user,
                'status' => $r->status,
                'date'   => (string) $r->created_at,
            ]);

        $usages = DB::table('wh_item_usage')
            ->select('usage_number as number', 'created_by_name as user', 'status', 'created_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'type'   => 'usage',
                'number' => $r->number,
                'user'   => $r->user,
                'status' => $r->status,
                'date'   => (string) $r->created_at,
            ]);

        $requests = DB::table('wh_item_request')
            ->select('request_number as number', 'created_by_name as user', 'status', 'created_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'type'   => 'request',
                'number' => $r->number,
                'user'   => $r->user,
                'status' => $r->status,
                'date'   => (string) $r->created_at,
            ]);

        $all = $receipts->concat($usages)->concat($requests)->all();
        usort($all, fn ($a, $b) => strcmp((string) $b['date'], (string) $a['date']));

        return array_slice($all, 0, 10);
    }

    /**
     * Ringkasan approval yang menunggu, dikelompokkan per modul.
     *
     * @return array<int, array{module:string,count:int,route:string}>
     */
    private function pendingApprovals(): array
    {
        return [
            [
                'module' => 'Item Request',
                'count'  => (int) DB::table('wh_item_request')->where('status', 'Waiting Approval')->count(),
                'route'  => '/purchase/item_request',
            ],
            [
                'module' => 'Purchase Order',
                'count'  => (int) DB::table('wh_purchase_order')->where('status', 'Waiting Approval')->count(),
                'route'  => '/purchase/purchase_order',
            ],
            [
                'module' => 'Receive Item',
                'count'  => (int) DB::table('wh_receipt_item')->where('status', 'Waiting Approval')->count(),
                'route'  => '/purchase/receive_item',
            ],
            [
                'module' => 'Item Usage',
                'count'  => (int) DB::table('wh_item_usage')->where('status', 'Waiting Approval')->count(),
                'route'  => '/inventory/item_usage',
            ],
            [
                'module' => 'Stock Adjustment',
                'count'  => (int) DB::table('wh_stock_adjustment')->where('status', 'Waiting Approval')->count(),
                'route'  => '/inventory/stock_adjustment',
            ],
        ];
    }
}
