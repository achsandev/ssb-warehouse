<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemUnitsResource;
use App\Models\ItemUnits;
use App\Traits\ApiResponse;

class UnitLookupController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $query = ItemUnits::query();

        $data = $query
            ->orderBy('name', 'asc')
            ->get([
                'id',
                'uid',
                'name',
                'symbol',
                'created_by_id',
                'created_by_name',
                'updated_by_id',
                'updated_by_name',
            ]);

        return $this->successResponse(
            ItemUnitsResource::collection($data),
            'List of item units'
        );
    }

    public function show($uid)
    {
        $data = ItemUnits::where('uid', $uid)
            ->orderBy('name', 'asc')
            ->firstOrFail([
                'id',
                'uid',
                'name',
                'symbol',
                'created_by_id',
                'created_by_name',
                'updated_by_id',
                'updated_by_name',
            ]);

        return $this->successResponse(
            new ItemUnitsResource($data),
            'Item unit detail'
        );
    }
}
