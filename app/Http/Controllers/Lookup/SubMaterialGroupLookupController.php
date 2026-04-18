<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Models\SubMaterialGroups;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SubMaterialGroupLookupController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $material_group_uid = $request->string('material_group_uid')->toString();

        $query = SubMaterialGroups::with(['material_group:id,uid,name']);

        if ($material_group_uid) {
            $query->whereHas('material_group', fn ($q) => $q->where('uid', $material_group_uid));
        }

        if ($search) {
            $query->where('name', 'like', "{$search}%");
        }

        $data = $query->orderBy('name')->get(['id', 'uid', 'material_group_id', 'name']);

        return $this->successResponse($data->map(fn ($sub) => [
            'uid'            => $sub->uid,
            'name'           => $sub->name,
            'material_group' => $sub->material_group ? ['uid' => $sub->material_group->uid, 'name' => $sub->material_group->name] : null,
        ]), 'List of sub material groups');
    }

    public function show(string $uid)
    {
        $sub = SubMaterialGroups::with(['material_group:id,uid,name'])->where('uid', $uid)->firstOrFail(['id', 'uid', 'material_group_id', 'name']);

        return $this->successResponse([
            'uid'            => $sub->uid,
            'name'           => $sub->name,
            'material_group' => $sub->material_group ? ['uid' => $sub->material_group->uid, 'name' => $sub->material_group->name] : null,
        ], 'Sub material group details');
    }
}
