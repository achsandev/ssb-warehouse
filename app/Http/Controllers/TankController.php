<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tank\StoreRequest;
use App\Http\Requests\Tank\UpdateRequest;
use App\Http\Resources\TankResource;
use App\Models\Tank;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TankController extends Controller
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
            'name',
            'created_at',
        ];

        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = Tank::with([
            'warehouse' => function ($warehouse) {
                $warehouse->select('id', 'uid', 'name');
            },
        ]);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'uid',
                    'warehouse_id',
                    'name',
                    'additional_info',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                TankResource::collection($data),
                'List of tank'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'uid',
                    'warehouse_id',
                    'name',
                    'additional_info',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(
            TankResource::collection($data),
            'List of tank'
        );
    }

    public function get_by_warehouse_uid(Request $request, string $uid)
    {
        $page = $request->integer('page', 1);
        $per_page = $request->integer('per_page', 10);
        $sort_by = $request->string('sort_by', 'created_at')->toString();
        $sort_dir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $warehouse_data = Warehouse::where('uid', $uid)->firstOrFail();

        $allowed_sort = [
            'name',
            'created_at',
        ];

        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = Tank::with([
            'warehouse' => function ($warehouse) {
                $warehouse->select('id', 'uid', 'name');
            },
        ])->where('warehouse_id', $warehouse_data->id);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'uid',
                    'warehouse_id',
                    'name',
                    'additional_info',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                TankResource::collection($data),
                'List of tank'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'uid',
                    'warehouse_id',
                    'name',
                    'additional_info',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(
            TankResource::collection($data),
            'List of tank'
        );
    }

    public function store(StoreRequest $request)
    {
        $warehouse = Warehouse::where('uid', $request->warehouse_uid)->firstOrFail();

        $data = $request->validated();
        unset($data['warehouse_uid']);
        $data['warehouse_id'] = $warehouse->id;
        $data['warehouse_name'] = $warehouse->name;

        $shelves = Tank::create($data);

        return $this->successResponse(
            new TankResource($shelves),
            'Tank created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid)
    {
        $rack = Tank::where('uid', $uid)->firstOrFail();

        $rack->update($request->validated());

        return $this->successResponse(
            new TankResource($rack),
            'Tank updated successfully'
        );
    }

    public function destroy(string $uid)
    {
        $rack = Tank::where('uid', $uid)->first();

        if (! $rack) {
            return $this->errorResponse('Tank not found', null, 404);
        }

        $rack->delete();

        return $this->successResponse(
            null,
            'Tank deleted successfully',
            200
        );
    }
}
