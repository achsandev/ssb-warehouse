<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemRequestResource;
use App\Models\ItemRequest;
use App\Traits\ApiResponse;

class ItemRequestLookupController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $query = ItemRequest::with([
            'item_request_detail' => function ($detail) {
                $detail->select('uid', 'item_request_id', 'item_id', 'unit_id', 'qty', 'description', 'created_at', 'updated_at', 'created_by_name', 'updated_by_name')
                    ->with(['item' => function ($item) {
                        $item->select('id', 'uid', 'code', 'name');
                    }])
                    ->with(['unit' => function ($unit) {
                        $unit->select('id', 'uid', 'name', 'symbol');
                    }]);
            },
            'approverSetting',
            'warehouse',
        ]);

        $data = $query
            ->orderBy('created_at', 'desc')
            ->get([
                'id',
                'uid',
                'requirement',
                'request_number',
                'request_date',
                'unit_code',
                'wo_number',
                'warehouse_id',
                'project_name',
                'department_name',
                'status',
                'created_by_role_id',
                'created_at',
                'updated_at',
                'created_by_name',
                'updated_by_name',
            ]);

        return $this->successResponse(
            ItemRequestResource::collection($data),
            'List of item request'
        );
    }

    public function show(string $uid)
    {
        $item_request = ItemRequest::with([
            'item_request_detail' => function ($detail) {
                $detail->select('uid', 'item_request_id', 'item_id', 'unit_id', 'qty', 'description', 'created_at', 'updated_at', 'created_by_name', 'updated_by_name')
                    ->with(['item' => function ($item) {
                        $item->select('id', 'uid', 'code', 'name');
                    }])
                    ->with(['unit' => function ($unit) {
                        $unit->select('id', 'uid', 'name', 'symbol');
                    }]);
            },
            'approverSetting',
            'warehouse',
        ])->where('uid', $uid)->firstOrFail([
            'id',
            'uid',
            'requirement',
            'request_number',
            'request_date',
            'unit_code',
            'wo_number',
            'warehouse_id',
            'project_name',
            'department_name',
            'status',
            'created_by_role_id',
            'created_at',
            'updated_at',
            'created_by_name',
            'updated_by_name',
        ]);

        return $this->successResponse(
            new ItemRequestResource($item_request),
            'Item request detail'
        );
    }
}
