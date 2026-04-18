<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReturnItemResource;
use App\Models\ReturnItem;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ReturnItemLookupController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $sort_by = $request->string('sort_by', 'created_at')->toString();
        $sort_dir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowed_sort = ['return_number', 'return_date', 'project_name', 'status', 'created_at'];
        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

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

        $data = $query->orderBy($sort_by, $sort_dir)->get([
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
        ]);

        return $this->successResponse(
            ReturnItemResource::collection($data),
            'List of return items'
        );
    }

    public function show(string $uid)
    {
        $returnItem = ReturnItem::with([
            'purchaseOrder:id,uid,po_number',
            'details.item:id,uid,name,code',
            'details.unit:id,uid,name,symbol',
        ])->where('uid', $uid)->firstOrFail([
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
        ]);

        return $this->successResponse(
            new ReturnItemResource($returnItem),
            'Return item details'
        );
    }
}
