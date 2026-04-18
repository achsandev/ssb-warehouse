<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashPurchase\StoreRequest;
use App\Http\Requests\CashPurchase\UpdateRequest;
use App\Http\Resources\CashPurchaseResource;
use App\Models\CashPurchase;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashPurchaseController extends Controller
{
    use ApiResponse;

    // ── Lookups ───────────────────────────────────────────────────────────────

    public function warehousesLookup(): JsonResponse
    {
        $warehouses = Warehouse::select('id', 'uid', 'name', 'cash_balance')
            ->orderBy('name')
            ->get()
            ->map(fn ($w) => [
                'uid'          => $w->uid,
                'name'         => $w->name,
                'cash_balance' => $w->cash_balance,
            ]);

        return $this->successResponse($warehouses, 'Warehouse lookup');
    }

    public function purchaseOrdersLookup(): JsonResponse
    {
        $pos = PurchaseOrder::select('id', 'uid', 'po_number', 'po_date', 'project_name', 'total_amount', 'status')
            ->with('details.item:id,name', 'details.unit:id,symbol')
            ->where('status', 'Approved')
            ->orderByDesc('po_date')
            ->get()
            ->map(fn ($po) => [
                'uid'          => $po->uid,
                'po_number'    => $po->po_number,
                'po_date'      => $po->po_date,
                'project_name' => $po->project_name,
                'total_amount' => $po->total_amount,
                'details'      => $po->details->map(fn ($d) => [
                    'item_name'   => $d->item?->name ?? '-',
                    'unit_symbol' => $d->unit?->symbol ?? '-',
                    'qty'         => $d->qty,
                    'price'       => $d->price,
                    'total'       => $d->total,
                ]),
            ]);

        return $this->successResponse($pos, 'Purchase order lookup');
    }

    // ── CRUD ──────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $page     = $request->integer('page', 1);
        $perPage  = $request->integer('per_page', 10);
        $sortBy   = $request->string('sort_by', 'created_at')->toString();
        $sortDir  = $request->string('sort_dir', 'desc')->toString();
        $search   = $request->string('search')->toString();

        $allowedSort = ['purchase_number', 'purchase_date', 'warehouse_name', 'po_number', 'po_total_amount', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSort)) {
            $sortBy = 'created_at';
        }

        $query = CashPurchase::with(['warehouse:id,uid,name,cash_balance', 'purchaseOrder:id,uid,po_number,total_amount']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('purchase_number', 'like', "{$search}%")
                    ->orWhere('po_number', 'like', "%{$search}%")
                    ->orWhere('warehouse_name', 'like', "%{$search}%");
            });
        }

        if ($perPage === -1) {
            $data = $query->orderBy($sortBy, $sortDir)->get();
            return $this->successResponse(CashPurchaseResource::collection($data), 'List of cash purchases');
        }

        $data = $query->orderBy($sortBy, $sortDir)->paginate($perPage, ['*'], 'page', $page);
        return $this->successResponse(CashPurchaseResource::collection($data), 'List of cash purchases');
    }

    public function show(string $uid): JsonResponse
    {
        $record = CashPurchase::with([
            'warehouse:id,uid,name,cash_balance',
            'purchaseOrder.details.item:id,name',
            'purchaseOrder.details.unit:id,symbol',
        ])->where('uid', $uid)->firstOrFail();

        return $this->successResponse(new CashPurchaseResource($record), 'Cash purchase detail');
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $warehouse = Warehouse::select('id', 'name', 'cash_balance')
            ->where('uid', $request->input('warehouse_uid'))
            ->firstOrFail();

        $po = PurchaseOrder::select('id', 'po_number', 'total_amount')
            ->where('uid', $request->input('po_uid'))
            ->firstOrFail();

        $record = CashPurchase::create([
            'purchase_date'  => $request->input('purchase_date'),
            'warehouse_id'   => $warehouse->id,
            'warehouse_name' => $warehouse->name,
            'po_id'          => $po->id,
            'po_number'      => $po->po_number,
            'po_total_amount'=> $po->total_amount,
            'notes'          => $request->input('notes'),
        ]);

        return $this->successResponse(
            new CashPurchaseResource($record->load('warehouse', 'purchaseOrder.details.item', 'purchaseOrder.details.unit')),
            'Cash purchase created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid): JsonResponse
    {
        $record = CashPurchase::where('uid', $uid)->firstOrFail();

        $warehouse = Warehouse::select('id', 'name', 'cash_balance')
            ->where('uid', $request->input('warehouse_uid'))
            ->firstOrFail();

        $po = PurchaseOrder::select('id', 'po_number', 'total_amount')
            ->where('uid', $request->input('po_uid'))
            ->firstOrFail();

        $previousStatus = $record->status;
        $newStatus      = $request->input('status');

        DB::transaction(function () use ($record, $warehouse, $po, $request, $previousStatus, $newStatus) {
            $record->update([
                'purchase_date'  => $request->input('purchase_date'),
                'warehouse_id'   => $warehouse->id,
                'warehouse_name' => $warehouse->name,
                'po_id'          => $po->id,
                'po_number'      => $po->po_number,
                'po_total_amount'=> $po->total_amount,
                'notes'          => $request->input('notes'),
                'status'         => $newStatus,
            ]);

            // On first approval: deduct cash balance from warehouse
            if ($newStatus === 'Approved' && $previousStatus !== 'Approved') {
                Warehouse::where('id', $warehouse->id)
                    ->lockForUpdate()
                    ->decrement('cash_balance', $po->total_amount);
            }
        });

        return $this->successResponse(
            new CashPurchaseResource($record->load('warehouse', 'purchaseOrder.details.item', 'purchaseOrder.details.unit')),
            'Cash purchase updated successfully'
        );
    }

    public function destroy(string $uid): JsonResponse
    {
        $record = CashPurchase::where('uid', $uid)->firstOrFail();

        if (! in_array($record->status, ['Waiting Approval', 'Revised'])) {
            return $this->errorResponse('Cannot delete a cash purchase that has been approved or rejected.', 422);
        }

        $record->delete();
        return $this->successResponse(null, 'Cash purchase deleted successfully');
    }
}
