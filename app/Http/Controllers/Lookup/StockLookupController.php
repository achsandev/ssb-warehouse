<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockResource;
use App\Models\Stock;
use App\Traits\ApiResponse;

class StockLookupController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $query = Stock::with([
            'item' => function ($item) {
                $item->select('id', 'uid', 'name');
            },
            'unit' => function ($unit) {
                $unit->select('id', 'uid', 'name', 'symbol');
            },
            'warehouse' => function ($warehouse) {
                $warehouse->select('id', 'uid', 'name');
            },
            'rack' => function ($rack) {
                $rack->select('id', 'uid', 'name');
            },
            'tank' => function ($tank) {
                $tank->select('id', 'uid', 'name');
            },
            'stock_units' => function ($q) {
                $q->select('id', 'uid', 'stock_id', 'unit_id', 'qty')
                    ->with(['unit:id,uid,name,symbol']);
            },
        ]);

        $data = $query
            ->orderBy('item_name', 'asc')
            ->get([
                'id',
                'uid',
                'item_id',
                'item_name',
                'unit_id',
                'unit_name',
                'unit_symbol',
                'warehouse_id',
                'warehouse_name',
                'rack_id',
                'rack_name',
                'tank_id',
                'tank_name',
                'qty',
                'created_at',
                'updated_at',
                'created_by_name',
                'updated_by_name',
            ]);

        return $this->successResponse(
            StockResource::collection($data),
            'List of stock'
        );
    }

    public function show(string $uid)
    {
        $stock = Stock::with([
            'item' => function ($item) {
                $item->select('id', 'uid', 'name');
            },
            'unit' => function ($unit) {
                $unit->select('id', 'uid', 'name', 'symbol');
            },
            'warehouse' => function ($warehouse) {
                $warehouse->select('id', 'uid', 'name');
            },
            'rack' => function ($rack) {
                $rack->select('id', 'uid', 'name');
            },
            'tank' => function ($tank) {
                $tank->select('id', 'uid', 'name');
            },
            'stock_units' => function ($q) {
                $q->select('id', 'uid', 'stock_id', 'unit_id', 'qty')
                    ->with(['unit:id,uid,name,symbol']);
            },
        ])->where('uid', $uid)->firstOrFail();

        return $this->successResponse(
            new StockResource($stock),
            'Stock details'
        );
    }
}
