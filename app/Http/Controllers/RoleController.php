<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return Role::with('permissions')->get();
    }

    public function store(Request $request)
    {
        $role = Role::create($request->only(['name', 'guard_name']));
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return $role->load('permissions');
    }

    public function show(Role $role)
    {
        return $role->load('permissions');
    }

    public function update(Request $request, Role $role)
    {
        $role->update($request->only(['name', 'guard_name']));
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return $role->load('permissions');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return response()->noContent();
    }
}
