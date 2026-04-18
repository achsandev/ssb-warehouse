<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Http\Resources\RoleResource;
use App\Http\Requests\RoleRequest;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return RoleResource::collection($roles);
    }

    public function store(RoleRequest $request)
    {
        $role = Role::create(['name' => $request->name, 'guard_name' => $request->guard_name ?? 'web']);
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return new RoleResource($role->load('permissions'));
    }

    public function show(Role $role)
    {
        return new RoleResource($role->load('permissions'));
    }

    public function update(RoleRequest $request, Role $role)
    {
        $role->update(['name' => $request->name, 'guard_name' => $request->guard_name ?? 'web']);
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return new RoleResource($role->load('permissions'));
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return response()->noContent();
    }

    public function permissions()
    {
        return Permission::all();
    }
}
