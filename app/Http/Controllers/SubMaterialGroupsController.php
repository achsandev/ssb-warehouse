<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubMaterialGroups\StoreRequest;
use App\Http\Requests\SubMaterialGroups\UpdateRequest;
use App\Http\Resources\SubMaterialGroupsResource;
use App\Models\MaterialGroups;
use App\Models\SubMaterialGroups;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SubMaterialGroupsController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $page = $request->integer('page', 1);
        $per_page = $request->integer('per_page', 10);
        $sort_by = $request->string('sort_by', 'created_at')->toString();
        $sort_dir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowed_sort = [
            'code',
            'name',
            'created_at',
        ];

        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = SubMaterialGroups::with([
            'material_group' => function ($material_group) {
                $material_group->select('id', 'uid', 'code', 'name');
            },
        ]);

        if ($search) {
            $query->where('code', 'like', "{$search}%")
                ->orWhere('name', 'like', "{$search}%");
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'uid',
                    'material_group_id',
                    'code',
                    'name',
                    'created_by_name',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                SubMaterialGroupsResource::collection($data),
                'List of sub material group'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'uid',
                    'material_group_id',
                    'code',
                    'name',
                    'created_by_name',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(
            SubMaterialGroupsResource::collection($data),
            'List of sub material group'
        );
    }

    public function get_by_uid(string $uid)
    {
        $query = SubMaterialGroups::with([
            'material_group' => function ($material_group) {
                $material_group->select('id', 'uid', 'code', 'name');
            },
        ])->select(
            'uid',
            'material_group_id',
            'code',
            'name',
            'created_at',
            'updated_at',
            'created_by_name',
            'updated_by_name'
        )->where('uid', $uid);

        $data = $query->get();

        return $this->successResponse(
            SubMaterialGroupsResource::collection($data),
            'List of sub material group'
        );
    }

    public function get_by_material_group_uid(Request $request, string $material_group_uid)
    {
        $page = $request->integer('page', 1);
        $per_page = $request->integer('per_page', 10);
        $sort_by = $request->string('sort_by', 'created_at')->toString();
        $sort_dir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $material_group_data = MaterialGroups::where('uid', $material_group_uid)->firstOrFail();

        $allowed_sort = [
            'code',
            'name',
            'created_at',
        ];

        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = SubMaterialGroups::with([
            'material_group' => function ($material_group) {
                $material_group->select('id', 'uid', 'code', 'name');
            },
        ])->where('material_group_id', $material_group_data->id);

        if ($search) {
            $query->where('code', 'like', "{$search}%")
                ->orWhere('name', 'like', "{$search}%");
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'uid',
                    'material_group_id',
                    'code',
                    'name',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                SubMaterialGroupsResource::collection($data),
                'List of sub material group'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'uid',
                    'material_group_id',
                    'code',
                    'name',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(
            SubMaterialGroupsResource::collection($data),
            'List of sub material group'
        );
    }

    public function store(StoreRequest $request)
    {
        $material_group = MaterialGroups::where('uid', $request->material_group_uid)->firstOrFail();

        $data = $request->validated();
        unset($data['material_group_uid']);
        $data['material_group_id'] = $material_group->id;
        $data['material_group_name'] = $material_group->name;

        $sub_material_group = SubMaterialGroups::create($data);

        return $this->successResponse(
            new SubMaterialGroupsResource($sub_material_group),
            'Sub material group created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid)
    {
        $sub_material_groups = SubMaterialGroups::where('uid', $uid)->firstOrFail();

        $sub_material_groups->update($request->validated());

        return $this->successResponse(
            new SubMaterialGroupsResource($sub_material_groups),
            'Sub material group updated successfully'
        );
    }

    public function destroy(string $uid)
    {
        $sub_material_groups = SubMaterialGroups::where('uid', $uid)->first();

        if (! $sub_material_groups) {
            return $this->errorResponse('Sub material group not found', null, 404);
        }

        $sub_material_groups->delete();

        return $this->successResponse(
            null,
            'Sub material group deleted successfully',
            200
        );
    }
}
