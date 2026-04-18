<?php

namespace App\Http\Controllers;

use App\Http\Resources\SettingPoApprovalResource;
use App\Models\SettingPoApproval;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SettingPoApprovalController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $page = $request->integer('page', 1);
        $per_page = $request->integer('per_page', 10);
        $sort_by = $request->string('sort_by', 'level')->toString();
        $sort_dir = $request->string('sort_dir', 'asc')->toString();
        $search = $request->string('search')->toString();

        $allowed_sort = ['level', 'role_name', 'min_amount', 'created_at'];

        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'level';
        }

        $query = SettingPoApproval::query();

        if ($search) {
            $query->whereHas('role', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $columns = ['id', 'uid', 'level', 'role_id', 'min_amount', 'description', 'is_active', 'created_at', 'updated_at', 'created_by_name', 'updated_by_name'];

        if ($per_page === -1) {
            $data = $query->with('role')->orderBy($sort_by, $sort_dir)->get($columns);

            return $this->successResponse(
                SettingPoApprovalResource::collection($data),
                'List of PO approval settings'
            );
        }

        $data = $query->with('role')->orderBy($sort_by, $sort_dir)->paginate($per_page, $columns, 'page', $page);

        return $this->successResponse(
            SettingPoApprovalResource::collection($data),
            'List of PO approval settings'
        );
    }

    public function show(string $uid)
    {
        $setting = SettingPoApproval::with('role')->where('uid', $uid)->firstOrFail();

        return $this->successResponse(
            new SettingPoApprovalResource($setting),
            'PO approval setting detail'
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'level'      => 'required|integer|unique:wh_setting_po_approval,level',
            'role_uid'   => 'required|exists:roles,uid',
            'min_amount' => 'nullable|numeric|min:0',
            'description'=> 'nullable|string|max:500',
            'is_active'  => 'boolean',
        ]);

        $role = \App\Models\Role::where('uid', $request->role_uid)->firstOrFail();

        $setting = SettingPoApproval::create([
            'level'       => $request->level,
            'role_id'     => $role->id,
            'min_amount'  => $request->min_amount,
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', true),
        ]);
        $setting->load('role');

        return $this->successResponse(
            new SettingPoApprovalResource($setting),
            'PO approval setting created successfully',
            201
        );
    }

    public function update(string $uid, Request $request)
    {
        $setting = SettingPoApproval::where('uid', $uid)->firstOrFail();

        $request->validate([
            'level'      => 'sometimes|integer|unique:wh_setting_po_approval,level,'.$setting->id,
            'role_uid'   => 'sometimes|exists:roles,uid',
            'min_amount' => 'nullable|numeric|min:0',
            'description'=> 'nullable|string|max:500',
            'is_active'  => 'boolean',
        ]);

        $data = $request->only(['level', 'min_amount', 'description', 'is_active']);

        if ($request->has('role_uid')) {
            $role = \App\Models\Role::where('uid', $request->role_uid)->firstOrFail();
            $data['role_id'] = $role->id;
        }

        $setting->update($data);
        $setting->load('role');

        return $this->successResponse(
            new SettingPoApprovalResource($setting),
            'PO approval setting updated successfully'
        );
    }

    public function delete(string $uid)
    {
        $setting = SettingPoApproval::where('uid', $uid)->firstOrFail();
        $setting->delete();

        return $this->successResponse(null, 'PO approval setting deleted successfully');
    }
}
