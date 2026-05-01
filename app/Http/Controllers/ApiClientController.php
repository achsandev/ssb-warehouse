<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ApiClient\StoreRequest;
use App\Http\Requests\ApiClient\UpdateRequest;
use App\Http\Resources\ApiClientResource;
use App\Models\ApiClient;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiClientController extends Controller
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

        $query = ApiClient::query();

        if ($search !== '') {
            $prefix = addcslashes($search, '%_\\').'%';
            $query->where(function (Builder $q) use ($prefix): void {
                $q->where('name', 'like', $prefix)
                    ->orWhere('url', 'like', $prefix);
            });
        }

        if ($perPage === -1) {
            $data = $query->orderBy($sortBy, $sortDir)->get();
            return $this->successResponse(
                ApiClientResource::collection($data),
                'List of API clients',
            );
        }

        $data = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->successResponse(
            ApiClientResource::collection($data),
            'List of API clients',
        );
    }

    public function show(string $uid): JsonResponse
    {
        $client = ApiClient::where('uid', $uid)->firstOrFail();

        return $this->successResponse(
            new ApiClientResource($client),
            'API client detail',
        );
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $client = ApiClient::create($request->validated());

        return $this->successResponse(
            new ApiClientResource($client),
            'API client created successfully',
            201,
        );
    }

    public function update(UpdateRequest $request, string $uid): JsonResponse
    {
        $client = ApiClient::where('uid', $uid)->firstOrFail();
        $client->update($request->validated());

        return $this->successResponse(
            new ApiClientResource($client->fresh()),
            'API client updated successfully',
        );
    }

    /**
     * Hapus permanen client + semua tokennya. Karena `personal_access_tokens`
     * tidak punya FK constraint ke `api_clients` (polymorphic), kita harus
     * delete token manual sebelum delete client supaya tidak ada orphan.
     */
    public function destroy(string $uid): JsonResponse
    {
        $client = ApiClient::where('uid', $uid)->firstOrFail();

        \DB::transaction(function () use ($client): void {
            // Hapus semua token yang tokenable_type=ApiClient dan id=client.id
            $client->tokens()->delete();
            $client->delete();
        });

        return $this->successResponse(null, 'API client deleted successfully');
    }
}
