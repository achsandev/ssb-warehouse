<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PermissionController extends Controller
{
    public function index()
    {
        return Permission::with('roles')->get();
    }

    public function store(Request $request)
    {
        $permission = Permission::create($request->only(['name', 'guard_name']));
        if ($request->has('roles')) {
            $permission->roles()->sync($request->roles);
        }
        return $permission->load('roles');
    }

    public function show(Permission $permission)
    {
        return $permission->load('roles');
    }

    public function update(Request $request, Permission $permission)
    {
        $permission->update($request->only(['name', 'guard_name']));
        if ($request->has('roles')) {
            $permission->roles()->sync($request->roles);
        }
        return $permission->load('roles');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return response()->noContent();
    }
}
