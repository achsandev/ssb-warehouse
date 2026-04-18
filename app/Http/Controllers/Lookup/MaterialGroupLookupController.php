<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Models\MaterialGroups;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class MaterialGroupLookupController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $search = $request->string('search')->toString();

        $query = MaterialGroups::query();

        if ($search) {
            $query->where('name', 'like', "{$search}%");
        }

        $data = $query->orderBy('name')->get(['uid', 'name']);

        return $this->successResponse($data, 'List of material groups');
    }

    public function show(string $uid)
    {
        $materialGroup = MaterialGroups::where('uid', $uid)->firstOrFail(['uid', 'name']);

        return $this->successResponse($materialGroup, 'Material group details');
    }
}
