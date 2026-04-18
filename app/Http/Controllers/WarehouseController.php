<?php

namespace App\Http\Controllers;

use App\Http\Requests\Warehouse\StoreRequest;
use App\Http\Requests\Warehouse\UpdateRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
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
            'address',
            'created_at',
        ];

        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = Warehouse::with([
            'rack' => function ($rack) {
                $rack->select('uid', 'warehouse_id', 'name');
            },
            'tank' => function ($tank) {
                $tank->select('uid', 'warehouse_id', 'name');
            },
        ]);

        if ($search) {
            $query->where('name', 'like', "{$search}%")
                ->orWhere('address', 'like', "{$search}%");
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'id',
                    'uid',
                    'name',
                    'address',
                    'additional_info',
                    'created_at',
                    'updated_at',
                    'created_by_id',
                    'created_by_name',
                    'updated_by_id',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                WarehouseResource::collection($data),
                'List of warehouse'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'id',
                    'uid',
                    'name',
                    'address',
                    'additional_info',
                    'created_at',
                    'updated_at',
                    'created_by_id',
                    'created_by_name',
                    'updated_by_id',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(
            WarehouseResource::collection($data),
            'List of warehouse'
        );
    }

    public function get_basic_info_by_uid(string $uid)
    {
        $query = Warehouse::select('uid', 'name', 'address', 'additional_info', 'created_at', 'updated_at', 'created_by_name', 'updated_by_name')
            ->where('uid', $uid);

        $data = $query->get();

        return $this->successResponse(
            WarehouseResource::collection($data)
                ->map
                ->basicInfo(),
            'Data of warehouse'
        );
    }

    public function store(StoreRequest $request)
    {
        $brand = Warehouse::create($request->validated());

        return $this->successResponse(
            new WarehouseResource($brand),
            'Warehouse created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid)
    {
        $item_brands = Warehouse::where('uid', $uid)->firstOrFail();

        $item_brands->update($request->validated());

        return $this->successResponse(
            new WarehouseResource($item_brands),
            'Warehouse updated successfully'
        );
    }

    public function destroy(string $uid)
    {
        $item_brands = Warehouse::where('uid', $uid)->first();

        if (! $item_brands) {
            return $this->errorResponse('Warehouse not found', null, 404);
        }

        $item_brands->delete();

        return $this->successResponse(
            null,
            'Warehouse deleted successfully',
            200
        );
    }
}
