<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Models\Tank;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TankLookupController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $warehouse_uid = $request->string('warehouse_uid')->toString();

        $query = Tank::with(['warehouse:id,uid,name']);

        if ($warehouse_uid) {
            $query->whereHas('warehouse', fn ($q) => $q->where('uid', $warehouse_uid));
        }

        if ($search) {
            $query->where('name', 'like', "{$search}%");
        }

        $data = $query->orderBy('name')->get(['id', 'uid', 'warehouse_id', 'name', 'warehouse_name']);

        return $this->successResponse($data->map(fn ($tank) => [
            'uid'       => $tank->uid,
            'name'      => $tank->name,
            'warehouse' => $tank->warehouse ? ['uid' => $tank->warehouse->uid, 'name' => $tank->warehouse->name] : null,
        ]), 'List of tanks');
    }

    public function show(string $uid)
    {
        $tank = Tank::with(['warehouse:id,uid,name'])->where('uid', $uid)->firstOrFail(['id', 'uid', 'warehouse_id', 'name', 'warehouse_name']);

        return $this->successResponse([
            'uid'       => $tank->uid,
            'name'      => $tank->name,
            'warehouse' => $tank->warehouse ? ['uid' => $tank->warehouse->uid, 'name' => $tank->warehouse->name] : null,
        ], 'Tank details');
    }
}
