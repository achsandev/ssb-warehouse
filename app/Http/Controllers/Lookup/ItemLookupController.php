<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemsResource;
use App\Models\Items;
use App\Traits\ApiResponse;

class ItemLookupController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $query = Items::with([
            'brand' => function ($brand) {
                $brand->select('id', 'uid', 'name');
            },
            'category' => function ($category) {
                $category->select('id', 'uid', 'name');
            },
            'unit' => function ($unit) {
                $unit->select('id', 'uid', 'symbol', 'name');
            },
            'movement_category' => function ($movement_category) {
                $movement_category->select('id', 'uid', 'name');
            },
            'valuation_method' => function ($valuation_method) {
                $valuation_method->select('id', 'uid', 'name');
            },
            'material_group' => function ($material_group) {
                $material_group->select('id', 'uid', 'code', 'name');
            },
            'sub_material_group' => function ($sub_material_group) {
                $sub_material_group->select('id', 'uid', 'code', 'name');
            },
            'supplier' => function ($supplier) {
                $supplier->select('id', 'uid', 'name');
            },
            'request_types' => function ($request_types) {
                $request_types->select('id', 'uid', 'name');
            },
            'item_unit_type' => function ($item_unit_type) {
                $item_unit_type->select('item_id', 'unit_type_id')
                    ->with(['usage_unit' => function ($usage_unit) {
                        $usage_unit->select('id', 'uid', 'name');
                    }]);
            },
        ]);

        $data = $query
            ->orderBy('name', 'asc')
            ->get([
                'id',
                'uid',
                'code',
                'name',
                'brand_id',
                'item_category_id',
                'unit_id',
                'min_qty',
                'part_number',
                'interchange_part',
                'image',
                'movement_category_id',
                'valuation_method_id',
                'material_group_id',
                'sub_material_group_id',
                'supplier_id',
                'price',
                'exp_date',
                'additional_info',
                'created_at',
                'updated_at',
                'created_by_name',
                'updated_by_name',
            ]);

        return $this->successResponse(
            ItemsResource::collection($data),
            'List of item'
        );
    }

    public function show($uid)
    {
        $query = Items::with([
            'brand' => function ($brand) {
                $brand->select('id', 'uid', 'name');
            },
            'category' => function ($category) {
                $category->select('id', 'uid', 'name');
            },
            'unit' => function ($unit) {
                $unit->select('id', 'uid', 'symbol', 'name');
            },
            'movement_category' => function ($movement_category) {
                $movement_category->select('id', 'uid', 'name');
            },
            'valuation_method' => function ($valuation_method) {
                $valuation_method->select('id', 'uid', 'name');
            },
            'material_group' => function ($material_group) {
                $material_group->select('id', 'uid', 'code', 'name');
            },
            'sub_material_group' => function ($sub_material_group) {
                $sub_material_group->select('id', 'uid', 'code', 'name');
            },
            'supplier' => function ($supplier) {
                $supplier->select('id', 'uid', 'name');
            },
            'request_types' => function ($request_types) {
                $request_types->select('id', 'uid', 'name');
            },
            'item_unit_type' => function ($item_unit_type) {
                $item_unit_type->select('item_id', 'unit_type_id')
                    ->with(['usage_unit' => function ($usage_unit) {
                        $usage_unit->select('id', 'uid', 'name');
                    }]);
            },
        ]);

        $data = $query
            ->orderBy('name', 'asc')
            ->where('uid', $uid)
            ->first([
                'id',
                'uid',
                'code',
                'name',
                'brand_id',
                'item_category_id',
                'unit_id',
                'min_qty',
                'part_number',
                'interchange_part',
                'image',
                'movement_category_id',
                'valuation_method_id',
                'material_group_id',
                'sub_material_group_id',
                'supplier_id',
                'price',
                'exp_date',
                'additional_info',
                'created_at',
                'updated_at',
                'created_by_name',
                'updated_by_name',
            ]);

        return $this->successResponse(
            new ItemsResource($data),
            'Item detail'
        );
    }
}
