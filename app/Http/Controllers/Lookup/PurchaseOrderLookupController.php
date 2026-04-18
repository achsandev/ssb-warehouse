<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PurchaseOrderLookupController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $sort_by = $request->string('sort_by', 'created_at')->toString();
        $sort_dir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowed_sort = ['po_number', 'po_date', 'project_name', 'status', 'created_at'];
        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = PurchaseOrder::with([
            'item_request:id,uid,request_number,project_name,wo_number',
            'details.item:id,uid,name',
            'details.unit:id,uid,name,symbol',
            'details.supplier:id,uid,name',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "{$search}%")
                    ->orWhere('project_name', 'like', "{$search}%");
            });
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->get([
                'id',
                'uid',
                'item_request_id',
                'po_number',
                'po_date',
                'project_name',
                'total_amount',
                'status',
                'created_at',
                'updated_at',
                'created_by_name',
                'updated_by_name',
            ]);

        return $this->successResponse(
            PurchaseOrderResource::collection($data),
            'List of purchase orders'
        );
    }

    public function show(string $uid)
    {
        $po = PurchaseOrder::with([
            'item_request:id,uid,request_number,project_name,wo_number',
            'details.item:id,uid,name',
            'details.unit:id,uid,name,symbol',
            'details.supplier:id,uid,name',
        ])->where('uid', $uid)->firstOrFail([
            'id',
            'uid',
            'item_request_id',
            'po_number',
            'po_date',
            'project_name',
            'total_amount',
            'status',
            'created_at',
            'updated_at',
            'created_by_name',
            'updated_by_name',
        ]);

        return $this->successResponse(
            new PurchaseOrderResource($po),
            'Purchase order details'
        );
    }
}
