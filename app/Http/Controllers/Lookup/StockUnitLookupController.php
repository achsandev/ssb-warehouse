<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Models\StockUnits;
use App\Traits\ApiResponse;

class StockUnitLookupController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $data = StockUnits::with(['unit:id,uid,name,symbol'])
            ->orderBy('id', 'asc')
            ->get();

        return $this->successResponse($data, 'List of stock units');
    }

    public function show(string $stockUid)
    {
        $stock = \App\Models\Stock::where('uid', $stockUid)->firstOrFail();

        $data = StockUnits::where('stock_id', $stock->id)
            ->with(['unit:id,uid,name,symbol'])
            ->get();

        return $this->successResponse($data, 'Stock units by stock');
    }
}
