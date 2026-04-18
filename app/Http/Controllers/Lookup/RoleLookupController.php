<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Traits\ApiResponse;

class RoleLookupController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $data = Role::orderBy('name', 'asc')
            ->get(['id', 'uid', 'name', 'guard_name']);

        return $this->successResponse($data, 'List of roles');
    }

    public function show($id)
    {
        $data = Role::findOrFail($uid, ['uid', 'name', 'guard_name']);

        return $this->successResponse($data, 'Role detail');
    }
}
