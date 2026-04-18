<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemUnits\StoreRequest;
use App\Http\Requests\ItemUnits\UpdateRequest;
use App\Http\Resources\ItemUnitsResource;
use App\Models\ItemUnits;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ItemUnitsController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $page = $request->integer('page', 1);
        $per_page = $request->integer('per_page', 10);
        $sort_by = $request->string('sort_by', 'created_at')->toString();
        $sort_dir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowed_sort = ['id', 'name', 'symbol', 'created_at'];
        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = ItemUnits::query();

        if ($search) {
            $query
                ->where('name', 'like', "{$search}%")
                ->orWhere('symbol', 'like', "{$search}%");
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'id',
                    'uid',
                    'name',
                    'symbol',
                    'created_by_id',
                    'created_by_name',
                    'updated_by_id',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                ItemUnitsResource::collection($data),
                'List of item units'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'id',
                    'uid',
                    'name',
                    'symbol',
                    'created_by_id',
                    'created_by_name',
                    'updated_by_id',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(
            ItemUnitsResource::collection($data),
            'List of item units'
        );
    }

    public function store(StoreRequest $request)
    {
        $brand = ItemUnits::create($request->validated());

        return $this->successResponse(
            new ItemUnitsResource($brand),
            'Item units created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid)
    {
        $item_brands = ItemUnits::where('uid', $uid)->firstOrFail();

        $item_brands->update($request->validated());

        return $this->successResponse(
            new ItemUnitsResource($item_brands),
            'Item units updated successfully'
        );
    }

    public function destroy(string $uid)
    {
        $item_brands = ItemUnits::where('uid', $uid)->first();

        if (! $item_brands) {
            return $this->errorResponse('Item units not found', null, 404);
        }

        $item_brands->delete();

        return $this->successResponse(
            null,
            'Item units deleted successfully',
            200
        );
    }
}
