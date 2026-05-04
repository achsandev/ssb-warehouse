<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SettingMinWarehouseCash\UpdateRequest;
use App\Http\Resources\SettingMinWarehouseCashResource;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Modul pengaturan ambang kas minimum per warehouse.
 *
 * Fokus murni pada field `min_cash` di tabel `wh_warehouse`. Tidak boleh
 * mengubah field lain (name, address, cash_balance, dll) — endpoint update
 * di sini sengaja hanya menerima `min_cash` di body request.
 *
 * Permission terpisah dari modul Warehouse:
 *   - `setting_min_warehouse_cash.read`
 *   - `setting_min_warehouse_cash.update`
 *
 * Memungkinkan admin yang hanya boleh mengatur threshold tanpa boleh edit
 * data master warehouse.
 */
class SettingMinWarehouseCashController extends Controller
{
    use ApiResponse;

    private const ALLOWED_SORT = ['name', 'cash_balance', 'min_cash', 'updated_at'];

    public function index(Request $request): JsonResponse
    {
        $page    = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 10);
        $sortBy  = $request->string('sort_by', 'name')->toString();
        $sortDir = strtolower($request->string('sort_dir', 'asc')->toString()) === 'desc' ? 'desc' : 'asc';
        $search  = $request->string('search')->toString();

        if (! in_array($sortBy, self::ALLOWED_SORT, true)) {
            $sortBy = 'name';
        }

        $columns = ['id', 'uid', 'name', 'address', 'cash_balance', 'min_cash', 'updated_at'];

        $query = Warehouse::query();

        if ($search !== '') {
            $prefix = addcslashes($search, '%_\\').'%';
            $query->where(function (Builder $q) use ($prefix): void {
                $q->where('name', 'like', $prefix)
                    ->orWhere('address', 'like', $prefix);
            });
        }

        if ($perPage === -1) {
            $data = $query->orderBy($sortBy, $sortDir)->get($columns);
            return $this->successResponse(
                SettingMinWarehouseCashResource::collection($data),
                'List of warehouse minimum cash settings',
            );
        }

        $data = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage, $columns, 'page', $page);

        return $this->successResponse(
            SettingMinWarehouseCashResource::collection($data),
            'List of warehouse minimum cash settings',
        );
    }

    public function show(string $uid): JsonResponse
    {
        $warehouse = Warehouse::where('uid', $uid)->firstOrFail([
            'id', 'uid', 'name', 'address', 'cash_balance', 'min_cash', 'updated_at',
        ]);

        return $this->successResponse(
            new SettingMinWarehouseCashResource($warehouse),
            'Warehouse minimum cash detail',
        );
    }

    public function update(UpdateRequest $request, string $uid): JsonResponse
    {
        $warehouse = Warehouse::where('uid', $uid)->firstOrFail();

        // Sengaja `update(['min_cash' => ...])` saja — TIDAK pakai
        // `$request->validated()` mass-assignment yang bisa terkontaminasi
        // payload field lain (mass assignment safety).
        $warehouse->update([
            'min_cash' => $request->input('min_cash'),
        ]);

        return $this->successResponse(
            new SettingMinWarehouseCashResource($warehouse->fresh()),
            'Minimum cash updated successfully',
        );
    }
}
