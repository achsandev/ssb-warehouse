<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestTypes\StoreRequest;
use App\Http\Requests\RequestTypes\UpdateRequest;
use App\Http\Resources\RequestTypesResource;
use App\Models\RequestTypes;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class RequestTypesController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $page = $request->integer('page', 1);
        $per_page = $request->integer('per_page', 10);
        $sort_by = $request->string('sort_by', 'created_at')->toString();
        $sort_dir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowed_sort = ['name', 'created_at'];
        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = RequestTypes::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'uid',
                    'name',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                RequestTypesResource::collection($data),
                'List of request type'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
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
            RequestTypesResource::collection($data),
            'List of request type'
        );
    }

    public function store(StoreRequest $request)
    {
        $usage_units = RequestTypes::create($request->validated());

        return $this->successResponse(
            new RequestTypesResource($usage_units),
            'Request type created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid)
    {
        $usage_units = RequestTypes::where('uid', $uid)->firstOrFail();

        $usage_units->update($request->validated());

        return $this->successResponse(
            new RequestTypesResource($usage_units),
            'Request type updated successfully'
        );
    }

    public function destroy(string $uid)
    {
        $usage_units = RequestTypes::where('uid', $uid)->first();

        if (! $usage_units) {
            return $this->errorResponse('Request type not found', null, 404);
        }

        $usage_units->delete();

        return $this->successResponse(
            null,
            'Request type deleted successfully',
            200
        );
    }
}
