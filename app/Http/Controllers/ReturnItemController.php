<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReturnItem\StoreRequest;
use App\Http\Requests\ReturnItem\UpdateRequest;
use App\Http\Resources\ReturnItemResource;
use App\Models\Items;
use App\Models\ItemUnits;
use App\Models\PurchaseOrder;
use App\Models\ReturnItem;
use App\Models\ReturnItemDetail;
use App\Models\StockUnits;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnItemController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 10);
        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortDir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowedSort = [
            'return_number',
            'return_date',
            'project_name',
            'status',
            'created_at',
        ];

        if (! in_array($sortBy, $allowedSort)) {
            $sortBy = 'created_at';
        }

        $columns = [
            'id',
            'uid',
            'return_number',
            'return_date',
            'purchase_order_id',
            'project_name',
            'status',
            'created_at',
            'updated_at',
            'created_by_name',
            'updated_by_name',
        ];

        $query = ReturnItem::with([
            'purchaseOrder:id,uid,po_number',
            'details.item:id,uid,name,code',
            'details.unit:id,uid,name,symbol',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "{$search}%")
                    ->orWhere('project_name', 'like', "{$search}%");
            });
        }

        if ($perPage === -1) {
            $data = $query->orderBy($sortBy, $sortDir)->get($columns);

            return $this->successResponse(
                ReturnItemResource::collection($data),
                'List of return items'
            );
        }

        $data = $query->orderBy($sortBy, $sortDir)->paginate($perPage, $columns, 'page', $page);

        return $this->successResponse(
            ReturnItemResource::collection($data),
            'List of return items'
        );
    }

    public function show(string $uid): JsonResponse
    {
        $returnItem = ReturnItem::with([
            'purchaseOrder:id,uid,po_number',
            'details.item:id,uid,name,code',
            'details.unit:id,uid,name,symbol',
        ])->where('uid', $uid)->firstOrFail();

        return $this->successResponse(
            new ReturnItemResource($returnItem),
            'Detail return item'
        );
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $purchaseOrder = PurchaseOrder::select('id')
            ->where('uid', $request->input('purchase_order_uid'))
            ->firstOrFail();

        $returnItem = ReturnItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'return_date' => $request->input('return_date'),
            'project_name' => $request->input('project_name'),
        ]);

        $this->syncDetails($returnItem, $request->input('details'));

        return $this->successResponse(
            new ReturnItemResource($returnItem->load('purchaseOrder', 'details.item', 'details.unit')),
            'Return item created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid): JsonResponse
    {
        $returnItem = ReturnItem::where('uid', $uid)->firstOrFail();

        $purchaseOrder = PurchaseOrder::select('id')
            ->where('uid', $request->input('purchase_order_uid'))
            ->firstOrFail();

        $previousStatus = $returnItem->status;
        $newStatus = $request->input('status');

        DB::transaction(function () use ($returnItem, $purchaseOrder, $request, $previousStatus, $newStatus) {
            $returnItem->update([
                'purchase_order_id' => $purchaseOrder->id,
                'return_date' => $request->input('return_date'),
                'project_name' => $request->input('project_name'),
                'status' => $newStatus,
            ]);

            // Replace details, get back resolved ids for stock increment
            $returnItem->details()->delete();
            $resolvedDetails = $this->syncDetails($returnItem, $request->input('details'));

            // Decrement StockUnits when status changes to approved
            if ($newStatus === 'Approved' && $previousStatus !== 'Approved') {
                foreach ($resolvedDetails as $resolved) {
                    StockUnits::join('wh_stocks', 'wh_stock_units.stock_id', '=', 'wh_stocks.id')
                        ->where('wh_stocks.item_id', $resolved['item_id'])
                        ->where('wh_stock_units.unit_id', $resolved['unit_id'])
                        ->lockForUpdate()
                        ->decrement('wh_stock_units.qty', $resolved['return_qty']);
                }
            }
        });

        return $this->successResponse(
            new ReturnItemResource($returnItem->load('purchaseOrder', 'details.item', 'details.unit')),
            'Return item updated successfully'
        );
    }

    public function destroy(string $uid): JsonResponse
    {
        $returnItem = ReturnItem::where('uid', $uid)->firstOrFail();

        $returnItem->details()->delete();
        $returnItem->delete();

        return $this->successResponse(null, 'Return item deleted successfully');
    }

    /**
     * Create detail rows for a ReturnItem record.
     * Returns array of resolved [item_id, unit_id, return_qty] for each detail.
     */
    private function syncDetails(ReturnItem $returnItem, array $details): array
    {
        $resolved = [];

        foreach ($details as $detail) {
            $item = Items::select('id')
                ->where('uid', $detail['item_uid'])
                ->firstOrFail();

            $unit = ItemUnits::select('id')
                ->where('uid', $detail['unit_uid'])
                ->firstOrFail();

            ReturnItemDetail::create([
                'return_item_id' => $returnItem->id,
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'qty' => $detail['qty'],
                'return_qty' => $detail['return_qty'],
                'description' => $detail['description'] ?? null,
            ]);

            $resolved[] = [
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'return_qty' => $detail['return_qty'],
            ];
        }

        return $resolved;
    }
}
