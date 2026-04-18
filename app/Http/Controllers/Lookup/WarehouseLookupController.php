<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class WarehouseLookupController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $search = $request->string('search')->toString();

        $query = Warehouse::query();

        if ($search) {
            $query->where('name', 'like', "{$search}%");
        }

        $data = $query->orderBy('name')->get(['uid', 'name']);

        return $this->successResponse($data, 'List of warehouses');
    }

    public function show(string $uid)
    {
        $warehouse = Warehouse::where('uid', $uid)->firstOrFail(['uid', 'name']);

        return $this->successResponse($warehouse, 'Warehouse details');
    }
}
