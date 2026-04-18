<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemUsage\StoreRequest;
use App\Http\Requests\ItemUsage\UpdateRequest;
use App\Http\Resources\ItemUsageResource;
use App\Models\ItemRequest;
use App\Models\Items;
use App\Models\ItemUnits;
use App\Models\ItemUsage;
use App\Models\ItemUsageDetail;
use App\Models\StockUnits;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemUsageController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 10);
        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortDir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowedSort = [
            'usage_number',
            'usage_date',
            'project_name',
            'status',
            'created_at',
        ];

        if (! in_array($sortBy, $allowedSort)) {
            $sortBy = 'created_at';
        }

        $columns = [
            'id',
            'uid',
            'usage_number',
            'usage_date',
            'item_request_id',
            'project_name',
            'status',
            'created_at',
            'updated_at',
            'created_by_name',
            'updated_by_name',
        ];

        $query = ItemUsage::with([
            'itemRequest:id,uid,request_number',
            'details.item:id,uid,name',
            'details.unit:id,uid,name,symbol',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('usage_number', 'like', "{$search}%")
                    ->orWhere('project_name', 'like', "{$search}%");
            });
        }

        if ($perPage === -1) {
            $data = $query->orderBy($sortBy, $sortDir)->get($columns);

            return $this->successResponse(
                ItemUsageResource::collection($data),
                'List of item usage'
            );
        }

        $data = $query->orderBy($sortBy, $sortDir)->paginate($perPage, $columns, 'page', $page);

        return $this->successResponse(
            ItemUsageResource::collection($data),
            'List of item usage'
        );
    }

    public function show(string $uid): JsonResponse
    {
        $itemUsage = ItemUsage::with([
            'itemRequest:id,uid,request_number',
            'details.item:id,uid,name',
            'details.unit:id,uid,name,symbol',
        ])->where('uid', $uid)->firstOrFail();

        return $this->successResponse(
            new ItemUsageResource($itemUsage),
            'Detail item usage'
        );
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $itemRequest = ItemRequest::select('id')
            ->where('uid', $request->input('item_request_uid'))
            ->firstOrFail();

        $itemUsage = ItemUsage::create([
            'item_request_id' => $itemRequest->id,
            'usage_date' => $request->input('usage_date'),
            'project_name' => $request->input('project_name'),
        ]);

        $this->syncDetails($itemUsage, $request->input('details'));

        return $this->successResponse(
            new ItemUsageResource($itemUsage->load('itemRequest', 'details.item', 'details.unit')),
            'Item usage created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid): JsonResponse
    {
        $itemUsage = ItemUsage::where('uid', $uid)->firstOrFail();

        $itemRequest = ItemRequest::select('id')
            ->where('uid', $request->input('item_request_uid'))
            ->firstOrFail();

        $previousStatus = $itemUsage->status;
        $newStatus = $request->input('status');

        DB::transaction(function () use ($itemUsage, $itemRequest, $request, $previousStatus, $newStatus) {
            $itemUsage->update([
                'item_request_id' => $itemRequest->id,
                'usage_date' => $request->input('usage_date'),
                'project_name' => $request->input('project_name'),
                'status' => $newStatus,
            ]);

            // Replace details, get back resolved ids for stock deduction
            $itemUsage->details()->delete();
            $resolvedDetails = $this->syncDetails($itemUsage, $request->input('details'));

            // Deduct StockUnits when status changes to approved
            if ($newStatus === 'Approved' && $previousStatus !== 'Approved') {
                foreach ($resolvedDetails as $resolved) {
                    StockUnits::where('item_id', $resolved['item_id'])
                        ->where('unit_id', $resolved['unit_id'])
                        ->lockForUpdate()
                        ->decrement('qty', $resolved['usage_qty']);
                }
            }
        });

        return $this->successResponse(
            new ItemUsageResource($itemUsage->load('itemRequest', 'details.item', 'details.unit')),
            'Item usage updated successfully'
        );
    }

    public function destroy(string $uid): JsonResponse
    {
        $itemUsage = ItemUsage::where('uid', $uid)->firstOrFail();

        $itemUsage->details()->delete();
        $itemUsage->delete();

        return $this->successResponse(null, 'Item usage deleted successfully');
    }

    /**
     * Create detail rows for an ItemUsage record.
     * Returns array of resolved [item_id, unit_id, usage_qty] for each detail.
     */
    private function syncDetails(ItemUsage $itemUsage, array $details): array
    {
        $resolved = [];

        foreach ($details as $detail) {
            $item = Items::select('id')
                ->where('uid', $detail['item_uid'])
                ->firstOrFail();

            $unit = ItemUnits::select('id')
                ->where('uid', $detail['unit_uid'])
                ->firstOrFail();

            ItemUsageDetail::create([
                'item_usage_id' => $itemUsage->id,
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'qty' => $detail['qty'],
                'usage_qty' => $detail['usage_qty'],
                'description' => $detail['description'] ?? null,
            ]);

            $resolved[] = [
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'usage_qty' => (float) $detail['usage_qty'],
            ];
        }

        return $resolved;
    }
}
