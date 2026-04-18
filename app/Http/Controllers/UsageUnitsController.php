<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsageUnits\StoreRequest;
use App\Http\Requests\UsageUnits\UpdateRequest;
use App\Http\Resources\UsageUnitsResource;
use App\Models\UsageUnits;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class UsageUnitsController extends Controller
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

        $query = UsageUnits::query();

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
                UsageUnitsResource::collection($data),
                'List of usage unit'
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
            UsageUnitsResource::collection($data),
            'List of usage unit'
        );
    }

    public function store(StoreRequest $request)
    {
        $usage_units = UsageUnits::create($request->validated());

        return $this->successResponse(
            new UsageUnitsResource($usage_units),
            'Usage unit created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid)
    {
        $usage_units = UsageUnits::where('uid', $uid)->firstOrFail();

        $usage_units->update($request->validated());

        return $this->successResponse(
            new UsageUnitsResource($usage_units),
            'Usage unit updated successfully'
        );
    }

    public function destroy(string $uid)
    {
        $usage_units = UsageUnits::where('uid', $uid)->first();

        if (! $usage_units) {
            return $this->errorResponse('Usage unit not found', null, 404);
        }

        $usage_units->delete();

        return $this->successResponse(
            null,
            'Usage unit deleted successfully',
            200
        );
    }
}
