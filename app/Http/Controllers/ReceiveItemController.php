<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceiveItem\StoreRequest;
use App\Http\Requests\ReceiveItem\UpdateRequest;
use App\Http\Resources\ReceiveItemResource;
use App\Models\Items;
use App\Models\ItemUnits;
use App\Models\PurchaseOrder;
use App\Models\ReceiveItem;
use App\Models\Stock;
use App\Models\StockUnits;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiveItemController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $page = $request->integer('page', 1);
        $per_page = $request->integer('per_page', 10);
        $sort_by = $request->string('sort_by', 'created_at')->toString();
        $sort_dir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowed_sort = [
            'receipt_number',
            'receipt_date',
            'status',
            'created_at',
        ];

        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = ReceiveItem::with([
            'purchase_order' => function ($purchase_order) {
                $purchase_order->select('id', 'uid', 'po_number');
            },
            'warehouse' => function ($warehouse) {
                $warehouse->select('id', 'uid', 'name');
            },
            'details.item:id,uid,name',
            'details.unit:id,uid,name,symbol',
            'details.supplier:id,uid,name',
        ]);

        if ($search) {
            $query->where('receipt_number', 'like', "{$search}%");
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'id',
                    'uid',
                    'receipt_number',
                    'receipt_date',
                    'project_name',
                    'purchase_order_id',
                    'warehouse_id',
                    'shipping_cost',
                    'status',
                    'reject_reason',
                    'additional_info',
                    'created_at',
                    'updated_at',
                    'created_by_id',
                    'updated_by_id',
                    'created_by_name',
                    'updated_by_name',
                ]);

            return $this->successResponse(ReceiveItemResource::collection($data), 'List of receipt items');
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'id',
                    'uid',
                    'receipt_number',
                    'receipt_date',
                    'project_name',
                    'purchase_order_id',
                    'warehouse_id',
                    'shipping_cost',
                    'status',
                    'reject_reason',
                    'additional_info',
                    'created_at',
                    'updated_at',
                    'created_by_id',
                    'updated_by_id',
                    'created_by_name',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(ReceiveItemResource::collection($data), 'List of receipt items');
    }

    public function store(StoreRequest $request)
    {
        $purchase_order_uid = $request->input('purchase_order_uid');
        $warehouse_uid = $request->input('warehouse_uid');
        $receipt_date = $request->input('receipt_date');
        $project_name = $request->input('project_name');
        $additional_info = $request->input('additional_info');
        $shipping_cost = $request->input('shipping_cost', 0);
        $details = $request->input('details');

        $purchase_order_id = PurchaseOrder::where('uid', $purchase_order_uid)->firstOrFail()->id;
        $warehouse_id = Warehouse::where('uid', $warehouse_uid)->firstOrFail()->id;

        $receiveItem = ReceiveItem::create([
            'receipt_date' => $receipt_date,
            'project_name' => $project_name,
            'purchase_order_id' => $purchase_order_id,
            'warehouse_id' => $warehouse_id,
            'shipping_cost' => $shipping_cost,
            'additional_info' => $additional_info,
        ]);

        foreach ($details as $detail) {
            $item_data = Items::where('uid', $detail['item_uid'])->firstOrFail();
            $unit_data = ItemUnits::where('uid', $detail['unit_uid'])->firstOrFail();
            $supplier_data = Supplier::where('uid', $detail['supplier_uid'])->firstOrFail();

            $receiveItem->details()->create([
                'item_id' => $item_data->id,
                'unit_id' => $unit_data->id,
                'supplier_id' => $supplier_data->id,
                'qty' => $detail['qty'],
                'price' => $detail['price'],
                'total' => $detail['total'],
                'qty_received' => $detail['qty_received'],
            ]);
        }

        return $this->successResponse(
            new ReceiveItemResource($receiveItem->load('details')),
            'Receive item created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid)
    {
        $receiveItem = ReceiveItem::where('uid', $uid)->firstOrFail();

        $previousStatus = $receiveItem->status;
        $newStatus = $request->input('status');
        $warehouse_uid = $request->input('warehouse_uid');
        $additional_info = $request->input('additional_info');
        $shipping_cost = $request->input('shipping_cost', 0);
        $reject_reason = $newStatus === 'Rejected' ? $request->input('reject_reason') : null;
        $details = $request->input('details');

        $warehouse_id = Warehouse::where('uid', $warehouse_uid)->firstOrFail()->id;
        $warehouse = Warehouse::findOrFail($warehouse_id);

        DB::transaction(function () use ($receiveItem, $warehouse_id, $warehouse, $newStatus, $additional_info, $shipping_cost, $reject_reason, $details, $previousStatus) {
            $receiveItem->update([
                'warehouse_id' => $warehouse_id,
                'shipping_cost' => $shipping_cost,
                'status' => $newStatus,
                'reject_reason' => $reject_reason,
                'additional_info' => $additional_info,
            ]);

            // Hapus semua detail yang lama
            $receiveItem->details()->delete();

            // Buat detail baru dan kumpulkan resolvedDetails untuk keperluan stock
            $resolvedDetails = [];

            foreach ($details as $detail) {
                $item_data = Items::where('uid', $detail['item_uid'])->firstOrFail();
                $unit_data = ItemUnits::where('uid', $detail['unit_uid'])->firstOrFail();
                $supplier_data = Supplier::where('uid', $detail['supplier_uid'])->firstOrFail();

                $receiveItem->details()->create([
                    'item_id' => $item_data->id,
                    'unit_id' => $unit_data->id,
                    'supplier_id' => $supplier_data->id,
                    'qty' => $detail['qty'],
                    'price' => $detail['price'],
                    'total' => $detail['total'],
                    'qty_received' => $detail['qty_received'],
                ]);

                $resolvedDetails[] = [
                    'item_id' => $item_data->id,
                    'item_name' => $item_data->name,
                    'unit_id' => $unit_data->id,
                    'unit_symbol' => $unit_data->symbol,
                    'qty_received' => $detail['qty_received'],
                ];
            }

            // Tambah / buat StockUnits qty saat status berubah menjadi Approved
            if ($newStatus === 'Approved' && $previousStatus !== 'Approved') {
                foreach ($resolvedDetails as $resolved) {
                    // Cari atau buat record Stock untuk item+warehouse ini
                    $stock = Stock::where('item_id', $resolved['item_id'])
                        ->where('warehouse_id', $warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $stock) {
                        $stock = Stock::create([
                            'item_id' => $resolved['item_id'],
                            'item_name' => $resolved['item_name'],
                            'warehouse_id' => $warehouse_id,
                            'warehouse_name' => $warehouse->name,
                            'unit_id' => $resolved['unit_id'],
                            'unit_symbol' => $resolved['unit_symbol'],
                            'qty' => 0,
                        ]);
                    }

                    // Cari atau buat record StockUnits untuk stock+unit ini
                    $stockUnit = StockUnits::where('stock_id', $stock->id)
                        ->where('unit_id', $resolved['unit_id'])
                        ->lockForUpdate()
                        ->first();

                    if (! $stockUnit) {
                        StockUnits::create([
                            'stock_id' => $stock->id,
                            'item_id' => $resolved['item_id'],
                            'unit_id' => $resolved['unit_id'],
                            'qty' => $resolved['qty_received'],
                        ]);
                    } else {
                        StockUnits::where('id', $stockUnit->id)
                            ->increment('qty', $resolved['qty_received']);
                    }
                }
            }
        });

        return $this->successResponse(
            new ReceiveItemResource($receiveItem->load('details')),
            'Receive item updated successfully'
        );
    }

    public function destroy(string $uid)
    {
        $receiveItem = ReceiveItem::where('uid', $uid)->firstOrFail();

        $receiveItem->delete();

        return $this->successResponse(
            null,
            'Receive item deleted successfully',
            204
        );
    }
}
