<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemRequest\StoreRequest;
use App\Http\Requests\ItemRequest\UpdateRequest;
use App\Http\Resources\ItemRequestResource;
use App\Models\ItemRequest;
use App\Models\ItemRequestDetail;
use App\Models\Items;
use App\Models\ItemUnits;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemRequestController extends Controller
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
            'requirement',
            'request_date',
            'project_name',
            'department_name',
            'created_at',
        ];

        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = ItemRequest::with([
            'item_request_detail' => function ($detail) {
                $detail->select('uid', 'item_request_id', 'item_id', 'unit_id', 'qty', 'description', 'created_at', 'updated_at', 'created_by_name', 'updated_by_name')
                    ->with(['item' => function ($item) {
                        $item->select('id', 'uid', 'code', 'name');
                    }])
                    ->with(['unit' => function ($unit) {
                        $unit->select('id', 'uid', 'name', 'symbol');
                    }]);
            },
            'approverSetting',
            'warehouse',
        ]);

        $user = auth()->user();
        $role = $user->roles->first();
        $roleName = $role->name ?? null;
        $isAdmin = in_array($roleName, ['superadmin', 'admin'], true) || $user->can('all.manage');
        if (! $isAdmin) {
            $roleId = $role->id ?? null;
            // Cari requester_role_id yang user ini boleh approve
            $approvedRequesterRoleIds = \App\Models\WhSettingApproverItemRequest::where('approver_role_id', $roleId)
                ->pluck('requester_role_id')
                ->toArray();

            $query->where(function ($q) use ($user, $approvedRequesterRoleIds) {
                // Tampilkan item request milik sendiri
                $q->where('created_by_id', $user->id);
                // Atau item request yang perlu di-approve oleh role ini
                if (!empty($approvedRequesterRoleIds)) {
                    $q->orWhereIn('created_by_role_id', $approvedRequesterRoleIds);
                }
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('project_name', 'like', "%{$search}%")
                    ->orWhere('department_name', 'like', "%{$search}%")
                    ->orWhere('unit_code', 'like', "%{$search}%")
                    ->orWhere('wo_number', 'like', "%{$search}%");
            });
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'id',
                    'uid',
                    'requirement',
                    'request_number',
                    'request_date',
                    'unit_code',
                    'wo_number',
                    'warehouse_id',
                    'is_project',
                    'project_name',
                    'department_name',
                    'status',
                    'reject_reason',
                    'created_by_role_id',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                ItemRequestResource::collection($data),
                'List of item request'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'id',
                    'uid',
                    'requirement',
                    'request_number',
                    'request_date',
                    'unit_code',
                    'wo_number',
                    'warehouse_id',
                    'is_project',
                    'project_name',
                    'department_name',
                    'status',
                    'reject_reason',
                    'created_by_role_id',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(
            ItemRequestResource::collection($data),
            'List of item request'
        );
    }

    public function show(string $uid)
    {
        $item_request = ItemRequest::with([
            'item_request_detail' => function ($detail) {
                $detail->select('uid', 'item_request_id', 'item_id', 'unit_id', 'qty', 'description', 'created_at', 'updated_at', 'created_by_name', 'updated_by_name')
                    ->with(['item' => function ($item) {
                        $item->select('id', 'uid', 'code', 'name');
                    }])
                    ->with(['unit' => function ($unit) {
                        $unit->select('id', 'uid', 'name', 'symbol');
                    }]);
            },
            'approverSetting',
        ])->where('uid', $uid)->firstOrFail();

        return $this->successResponse(
            new ItemRequestResource($item_request),
            'Item request details'
        );
    }

    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $details = $data['details'];
        unset($data['details']);

        $warehouse_uid = $data['warehouse_uid'] ?? null;
        unset($data['warehouse_uid']);
        $data['warehouse_id'] = Warehouse::where('uid', $warehouse_uid)->value('id');

        // Non-project → paksa project_name null supaya data tidak membingungkan.
        if (empty($data['is_project'])) {
            $data['project_name'] = null;
        }

        // Server-side injection
        $data['status'] = 'Waiting Approval';
        $data['created_by_role_id'] = $request->user()->roles()->first()?->id;

        $item_request = DB::transaction(function () use ($data, $details) {
            $ir = ItemRequest::create($data);
            $ir->load('warehouse');

            foreach ($details as $detail) {
                $item_data = Items::select('id')->where('uid', $detail['item_uid'])->firstOrFail();
                $unit_data = ItemUnits::select('id')->where('uid', $detail['unit_uid'])->firstOrFail();

                ItemRequestDetail::create([
                    'item_request_id' => $ir->id,
                    'item_id'         => $item_data->id,
                    'unit_id'         => $unit_data->id,
                    'qty'             => $detail['qty'],
                    'description'     => $detail['description'] ?? null,
                ]);
            }

            return $ir;
        });

        return $this->successResponse(
            new ItemRequestResource($item_request),
            'Item request created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid)
    {
        $item_request = ItemRequest::where('uid', $uid)->firstOrFail();

        DB::transaction(function () use ($request, $item_request) {
            $data = $request->validated();
            $details = $data['details'] ?? null;
            unset($data['details']);

            if (array_key_exists('warehouse_uid', $data)) {
                $warehouse_uid = $data['warehouse_uid'];
                unset($data['warehouse_uid']);
                $data['warehouse_id'] = Warehouse::where('uid', $warehouse_uid)->value('id');
            }

            if (array_key_exists('is_project', $data) && empty($data['is_project'])) {
                $data['project_name'] = null;
            }

            $item_request->update($data);

            if ($details) {
                ItemRequestDetail::where('item_request_id', $item_request->id)->delete();

                foreach ($details as $detail) {
                    $item_data = Items::select('id')->where('uid', $detail['item_uid'])->firstOrFail();
                    $unit_data = ItemUnits::select('id')->where('uid', $detail['unit_uid'])->firstOrFail();

                    ItemRequestDetail::create([
                        'item_request_id' => $item_request->id,
                        'item_id'         => $item_data->id,
                        'unit_id'         => $unit_data->id,
                        'qty'             => $detail['qty'],
                        'description'     => $detail['description'] ?? null,
                    ]);
                }
            }
        });

        return $this->successResponse(
            new ItemRequestResource($item_request->fresh(['item_request_detail.item', 'item_request_detail.unit', 'approverSetting', 'warehouse'])),
            'Item request updated successfully'
        );
    }

    public function destroy(string $uid)
    {
        $item_request = ItemRequest::where('uid', $uid)->firstOrFail();

        $item_request->delete();

        return $this->successResponse(
            null,
            'Item request deleted successfully',
            204
        );
    }
}
