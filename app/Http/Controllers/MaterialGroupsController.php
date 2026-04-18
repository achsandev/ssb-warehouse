<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaterialGroups\StoreRequest;
use App\Http\Requests\MaterialGroups\UpdateRequest;
use App\Http\Resources\MaterialGroupsResource;
use App\Models\MaterialGroups;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class MaterialGroupsController extends Controller
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

        $query = MaterialGroups::with([
            'sub_material_group' => function ($sub_material_group) {
                $sub_material_group->select('uid', 'material_group_id', 'code', 'name');
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
                    'id',
                    'uid',
                    'code',
                    'name',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                MaterialGroupsResource::collection($data),
                'List of material group'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'id',
                    'uid',
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
            MaterialGroupsResource::collection($data),
            'List of material group'
        );
    }

    public function get_basic_info_by_uid(string $uid)
    {
        $query = MaterialGroups::select('uid', 'code', 'name', 'created_by_name', 'updated_by_name')
            ->where('uid', $uid);

        $data = $query->get();

        return $this->successResponse(
            MaterialGroupsResource::collection($data)
                ->map
                ->basicInfo(),
            'Data of material group'
        );
    }

    public function store(StoreRequest $request)
    {
        $brand = MaterialGroups::create($request->validated());

        return $this->successResponse(
            new MaterialGroupsResource($brand),
            'Material group created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid)
    {
        $item_brands = MaterialGroups::where('uid', $uid)->firstOrFail();

        $item_brands->update($request->validated());

        return $this->successResponse(
            new MaterialGroupsResource($item_brands),
            'Material group updated successfully'
        );
    }

    public function destroy(string $uid)
    {
        $item_brands = MaterialGroups::where('uid', $uid)->first();

        if (! $item_brands) {
            return $this->errorResponse('Material group not found', null, 404);
        }

        $item_brands->delete();

        return $this->successResponse(
            null,
            'Material group deleted successfully',
            200
        );
    }
}
