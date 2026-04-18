<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Models\Rack;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class RackLookupController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $warehouse_uid = $request->string('warehouse_uid')->toString();

        $query = Rack::with(['warehouse:id,uid,name']);

        if ($warehouse_uid) {
            $query->whereHas('warehouse', fn ($q) => $q->where('uid', $warehouse_uid));
        }

        if ($search) {
            $query->where('name', 'like', "{$search}%");
        }

        $data = $query->orderBy('name')->get(['id', 'uid', 'warehouse_id', 'name', 'warehouse_name']);

        return $this->successResponse($data->map(fn ($rack) => [
            'uid'       => $rack->uid,
            'name'      => $rack->name,
            'warehouse' => $rack->warehouse ? ['uid' => $rack->warehouse->uid, 'name' => $rack->warehouse->name] : null,
        ]), 'List of racks');
    }

    public function show(string $uid)
    {
        $rack = Rack::with(['warehouse:id,uid,name'])->where('uid', $uid)->firstOrFail(['id', 'uid', 'warehouse_id', 'name', 'warehouse_name']);

        return $this->successResponse([
            'uid'       => $rack->uid,
            'name'      => $rack->name,
            'warehouse' => $rack->warehouse ? ['uid' => $rack->warehouse->uid, 'name' => $rack->warehouse->name] : null,
        ], 'Rack details');
    }
}
