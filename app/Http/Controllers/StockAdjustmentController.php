<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockAdjustment\StoreRequest;
use App\Http\Requests\StockAdjustment\UpdateRequest;
use App\Http\Resources\StockAdjustmentResource;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentDetail;
use App\Models\StockOpname;
use App\Models\StockUnits;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockAdjustmentController extends Controller
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
            'adjustment_number',
            'adjustment_date',
            'status',
            'created_at',
        ];

        if (! in_array($sortBy, $allowedSort)) {
            $sortBy = 'created_at';
        }

        $columns = [
            'id',
            'uid',
            'adjustment_number',
            'adjustment_date',
            'stock_opname_id',
            'stock_opname_number',
            'notes',
            'status',
            'created_at',
            'updated_at',
            'created_by_name',
            'updated_by_name',
        ];

        $query = StockAdjustment::with(['details.stockUnit', 'stockOpname:id,uid,opname_number']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_number', 'like', "{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($perPage === -1) {
            $data = $query->orderBy($sortBy, $sortDir)->get($columns);

            return $this->successResponse(
                StockAdjustmentResource::collection($data),
                'List of stock adjustments'
            );
        }

        $data = $query->orderBy($sortBy, $sortDir)->paginate($perPage, $columns, 'page', $page);

        return $this->successResponse(
            StockAdjustmentResource::collection($data),
            'List of stock adjustments'
        );
    }

    public function show(string $uid): JsonResponse
    {
        $adjustment = StockAdjustment::with(['details.stockUnit', 'stockOpname:id,uid,opname_number'])
            ->where('uid', $uid)
            ->firstOrFail();

        return $this->successResponse(
            new StockAdjustmentResource($adjustment),
            'Detail stock adjustment'
        );
    }

    public function store(StoreRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request, &$adjustment) {
            $stockOpname = null;
            $stockOpnameNumber = null;

            if ($request->filled('stock_opname_uid')) {
                $stockOpname = StockOpname::select('id', 'opname_number')
                    ->where('uid', $request->input('stock_opname_uid'))
                    ->firstOrFail();
                $stockOpnameNumber = $stockOpname->opname_number;
            }

            $adjustment = StockAdjustment::create([
                'adjustment_date'     => $request->input('adjustment_date'),
                'stock_opname_id'     => $stockOpname?->id,
                'stock_opname_number' => $stockOpnameNumber,
                'notes'               => $request->input('notes'),
            ]);

            $this->syncDetails($adjustment, $request->input('details'));
        });

        return $this->successResponse(
            new StockAdjustmentResource($adjustment->load('details.stockUnit', 'stockOpname')),
            'Stock adjustment created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid): JsonResponse
    {
        $adjustment = StockAdjustment::where('uid', $uid)->firstOrFail();
        $previousStatus = $adjustment->status;
        $newStatus = $request->input('status');

        DB::transaction(function () use ($adjustment, $request, $previousStatus, $newStatus) {
            $stockOpname = null;
            $stockOpnameNumber = null;

            if ($request->filled('stock_opname_uid')) {
                $stockOpname = StockOpname::select('id', 'opname_number')
                    ->where('uid', $request->input('stock_opname_uid'))
                    ->firstOrFail();
                $stockOpnameNumber = $stockOpname->opname_number;
            }

            $adjustment->update([
                'adjustment_date'     => $request->input('adjustment_date'),
                'stock_opname_id'     => $stockOpname?->id,
                'stock_opname_number' => $stockOpnameNumber,
                'notes'               => $request->input('notes'),
                'status'              => $newStatus,
            ]);

            // Replace details only when still editable (Draft or Revised)
            if (in_array($previousStatus, ['Draft', 'Revised'])) {
                $adjustment->details()->delete();
                $this->syncDetails($adjustment, $request->input('details'));
            }

            // On first approval: apply adjustment_qty to each StockUnit
            if ($newStatus === 'Approved' && $previousStatus !== 'Approved') {
                foreach ($adjustment->details as $detail) {
                    StockUnits::where('id', $detail->stock_unit_id)
                        ->lockForUpdate()
                        ->update(['qty' => DB::raw('qty + ' . (float) $detail->adjustment_qty)]);
                }
            }
        });

        return $this->successResponse(
            new StockAdjustmentResource($adjustment->load('details.stockUnit', 'stockOpname')),
            'Stock adjustment updated successfully'
        );
    }

    public function destroy(string $uid): JsonResponse
    {
        $adjustment = StockAdjustment::where('uid', $uid)->firstOrFail();

        abort_if($adjustment->status !== 'Draft', 422, 'Only Draft stock adjustments can be deleted.');

        $adjustment->details()->delete();
        $adjustment->delete();

        return $this->successResponse(null, 'Stock adjustment deleted successfully');
    }

    /**
     * Create detail rows by resolving each stock_unit_uid and snapshotting denormalized fields.
     */
    private function syncDetails(StockAdjustment $adjustment, array $details): void
    {
        foreach ($details as $row) {
            $stockUnit = StockUnits::with('stock')
                ->where('uid', $row['stock_unit_uid'])
                ->firstOrFail();

            $stock = $stockUnit->stock;

            StockAdjustmentDetail::create([
                'uid'                  => (string) Str::uuid(),
                'stock_adjustment_id'  => $adjustment->id,
                'stock_unit_id'        => $stockUnit->id,
                'item_id'              => $stock->item_id,
                'item_name'            => $stock->item_name,
                'unit_id'              => $stock->unit_id,
                'unit_symbol'          => $stock->unit_symbol,
                'warehouse_id'         => $stock->warehouse_id,
                'warehouse_name'       => $stock->warehouse_name,
                'rack_id'              => $stock->rack_id,
                'rack_name'            => $stock->rack_name,
                'adjustment_qty'       => (float) $row['adjustment_qty'],
                'notes'                => $row['notes'] ?? null,
            ]);
        }
    }
}
