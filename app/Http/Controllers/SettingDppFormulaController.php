<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingDppFormula\StoreRequest;
use App\Http\Requests\SettingDppFormula\UpdateRequest;
use App\Http\Resources\SettingDppFormulaResource;
use App\Models\SettingDppFormula;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingDppFormulaController extends Controller
{
    use ApiResponse;

    private const ALLOWED_SORT = ['name', 'is_active', 'created_at', 'updated_at'];

    public function index(Request $request): JsonResponse
    {
        $page    = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 10);
        $sortBy  = $request->string('sort_by', 'created_at')->toString();
        $sortDir = strtolower($request->string('sort_dir', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';
        $search  = $request->string('search')->toString();

        if (! in_array($sortBy, self::ALLOWED_SORT, true)) {
            $sortBy = 'created_at';
        }

        $query = SettingDppFormula::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "{$search}%")
                    ->orWhere('formula', 'like', "%{$search}%");
            });
        }

        $columns = [
            'id', 'uid', 'name', 'formula', 'description', 'is_active',
            'created_at', 'updated_at', 'created_by_name', 'updated_by_name',
        ];

        if ($perPage === -1) {
            $data = $query->orderBy($sortBy, $sortDir)->get($columns);

            return $this->successResponse(SettingDppFormulaResource::collection($data), 'List of DPP formula');
        }

        $data = $query->orderBy($sortBy, $sortDir)->paginate($perPage, $columns, 'page', $page);

        return $this->successResponse(SettingDppFormulaResource::collection($data), 'List of DPP formula');
    }

    public function show(string $uid): JsonResponse
    {
        $row = SettingDppFormula::where('uid', $uid)->firstOrFail();

        return $this->successResponse(new SettingDppFormulaResource($row), 'DPP formula detail');
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $row = SettingDppFormula::create($request->validated());

        return $this->successResponse(
            new SettingDppFormulaResource($row),
            'DPP formula created successfully',
            201,
        );
    }

    public function update(UpdateRequest $request, string $uid): JsonResponse
    {
        $row = SettingDppFormula::where('uid', $uid)->firstOrFail();
        $row->update($request->validated());

        return $this->successResponse(
            new SettingDppFormulaResource($row->fresh()),
            'DPP formula updated successfully',
        );
    }

    public function destroy(string $uid): JsonResponse
    {
        $row = SettingDppFormula::where('uid', $uid)->firstOrFail();
        $row->delete();

        return $this->successResponse(null, 'DPP formula deleted successfully');
    }
}
