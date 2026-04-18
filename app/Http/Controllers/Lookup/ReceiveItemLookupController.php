<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReceiveItemResource;
use App\Models\ReceiveItem;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ReceiveItemLookupController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $sort_by = $request->string('sort_by', 'created_at')->toString();
        $sort_dir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowed_sort = ['receipt_number', 'receipt_date', 'project_name', 'status', 'created_at'];
        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = ReceiveItem::with([
            'purchase_order:id,uid,po_number',
            'warehouse:id,uid,name',
            'details.item:id,uid,name,code',
            'details.unit:id,uid,name,symbol',
            'details.supplier:id,uid,name',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "{$search}%")
                    ->orWhere('project_name', 'like', "{$search}%");
            });
        }

        $data = $query->orderBy($sort_by, $sort_dir)->get([
            'id',
            'uid',
            'receipt_number',
            'receipt_date',
            'project_name',
            'purchase_order_id',
            'warehouse_id',
            'status',
            'additional_info',
            'created_at',
            'updated_at',
            'created_by_name',
            'updated_by_name',
        ]);

        return $this->successResponse(
            ReceiveItemResource::collection($data),
            'List of receive items'
        );
    }

    public function show(string $uid)
    {
        $receiveItem = ReceiveItem::with([
            'purchase_order:id,uid,po_number',
            'warehouse:id,uid,name',
            'details.item:id,uid,name,code',
            'details.unit:id,uid,name,symbol',
            'details.supplier:id,uid,name',
        ])->where('uid', $uid)->firstOrFail([
            'id',
            'uid',
            'receipt_number',
            'receipt_date',
            'project_name',
            'purchase_order_id',
            'warehouse_id',
            'status',
            'additional_info',
            'created_at',
            'updated_at',
            'created_by_name',
            'updated_by_name',
        ]);

        return $this->successResponse(
            new ReceiveItemResource($receiveItem),
            'Receive item details'
        );
    }
}
