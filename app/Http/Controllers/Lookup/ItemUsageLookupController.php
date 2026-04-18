<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemUsageResource;
use App\Models\ItemUsage;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ItemUsageLookupController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $sort_by = $request->string('sort_by', 'created_at')->toString();
        $sort_dir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowed_sort = ['usage_number', 'usage_date', 'project_name', 'status', 'created_at'];
        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = ItemUsage::with([
            'itemRequest:id,uid,request_number',
            'details.item:id,uid,name,code',
            'details.unit:id,uid,name,symbol',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('usage_number', 'like', "{$search}%")
                    ->orWhere('project_name', 'like', "{$search}%");
            });
        }

        $data = $query->orderBy($sort_by, $sort_dir)->get([
            'id',
            'uid',
            'usage_number',
            'usage_date',
            'item_request_id',
            'project_name',
            'status',
            'created_at',
            'updated_at',
            'created_by_name',
            'updated_by_name',
        ]);

        return $this->successResponse(
            ItemUsageResource::collection($data),
            'List of item usage'
        );
    }

    public function show(string $uid)
    {
        $itemUsage = ItemUsage::with([
            'itemRequest:id,uid,request_number',
            'details.item:id,uid,name,code',
            'details.unit:id,uid,name,symbol',
        ])->where('uid', $uid)->firstOrFail([
            'id',
            'uid',
            'usage_number',
            'usage_date',
            'item_request_id',
            'project_name',
            'status',
            'created_at',
            'updated_at',
            'created_by_name',
            'updated_by_name',
        ]);

        return $this->successResponse(
            new ItemUsageResource($itemUsage),
            'Item usage details'
        );
    }
}
