<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemBrands\StoreRequest;
use App\Http\Requests\ItemBrands\UpdateRequest;
use App\Http\Resources\ItemBrandsResource;
use App\Models\ItemBrands;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ItemBrandsController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $page = $request->integer('page', 1);
        $per_page = $request->integer('per_page', 10);
        $sort_by = $request->string('sort_by', 'created_at')->toString();
        $sort_dir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowed_sort = ['id', 'name', 'created_at'];
        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = ItemBrands::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'id',
                    'uid',
                    'name',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                ItemBrandsResource::collection($data),
                'List of item brands'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'id',
                    'uid',
                    'name',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(
            ItemBrandsResource::collection($data),
            'List of item brands'
        );
    }

    public function store(StoreRequest $request)
    {
        $brand = ItemBrands::create($request->validated());

        return $this->successResponse(
            new ItemBrandsResource($brand),
            'Item brands created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid)
    {
        $item_brands = ItemBrands::where('uid', $uid)->firstOrFail();

        $item_brands->update($request->validated());

        return $this->successResponse(
            new ItemBrandsResource($item_brands),
            'Item brands updated successfully'
        );
    }

    public function destroy(string $uid)
    {
        $item_brands = ItemBrands::where('uid', $uid)->first();

        if (! $item_brands) {
            return $this->errorResponse('Item brands not found', null, 404);
        }

        $item_brands->delete();

        return $this->successResponse(
            null,
            'Item brands deleted successfully',
            200
        );
    }
}
