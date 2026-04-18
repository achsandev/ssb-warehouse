<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemCategories\StoreRequest;
use App\Http\Requests\ItemCategories\UpdateRequest;
use App\Http\Resources\ItemCategoriesResource;
use App\Models\ItemCategories;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ItemCategoriesController extends Controller
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

        $query = ItemCategories::query();

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
                    'created_by_id',
                    'created_by_name',
                    'updated_by_id',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                ItemCategoriesResource::collection($data),
                'List of item categories'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'id',
                    'uid',
                    'name',
                    'created_by_id',
                    'created_by_name',
                    'updated_by_id',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(
            ItemCategoriesResource::collection($data),
            'List of item categories'
        );
    }

    public function store(StoreRequest $request)
    {
        $brand = ItemCategories::create($request->validated());

        return $this->successResponse(
            new ItemCategoriesResource($brand),
            'Item categories created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid)
    {
        $item_brands = ItemCategories::where('uid', $uid)->firstOrFail();

        $item_brands->update($request->validated());

        return $this->successResponse(
            new ItemCategoriesResource($item_brands),
            'Item categories updated successfully'
        );
    }

    public function destroy(string $uid)
    {
        $item_brands = ItemCategories::where('uid', $uid)->first();

        if (! $item_brands) {
            return $this->errorResponse('Item categories not found', null, 404);
        }

        $item_brands->delete();

        return $this->successResponse(
            null,
            'Item categories deleted successfully',
            200
        );
    }
}
