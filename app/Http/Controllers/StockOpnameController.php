<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockOpname\EnterCountRequest;
use App\Http\Requests\StockOpname\StoreRequest;
use App\Http\Requests\StockOpname\UpdateRequest;
use App\Http\Resources\StockOpnameResource;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentDetail;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Models\StockUnits;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockOpnameController extends Controller
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
            'opname_number',
            'opname_date',
            'status',
            'created_at',
        ];

        if (! in_array($sortBy, $allowedSort)) {
            $sortBy = 'created_at';
        }

        $columns = [
            'id',
            'uid',
            'opname_number',
            'opname_date',
            'notes',
            'status',
            'created_at',
            'updated_at',
            'created_by_name',
            'updated_by_name',
        ];

        $query = StockOpname::with(['details.stockUnit']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('opname_number', 'like', "{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($perPage === -1) {
            $data = $query->orderBy($sortBy, $sortDir)->get($columns);

            return $this->successResponse(
                StockOpnameResource::collection($data),
                'List of stock opnames'
            );
        }

        $data = $query->orderBy($sortBy, $sortDir)->paginate($perPage, $columns, 'page', $page);

        return $this->successResponse(
            StockOpnameResource::collection($data),
            'List of stock opnames'
        );
    }

    public function show(string $uid): JsonResponse
    {
        $opname = StockOpname::with(['details.stockUnit'])
            ->where('uid', $uid)
            ->firstOrFail();

        return $this->successResponse(
            new StockOpnameResource($opname),
            'Detail stock opname'
        );
    }

    public function store(StoreRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request, &$opname) {
            $opname = StockOpname::create([
                'opname_date' => $request->input('opname_date'),
                'notes' => $request->input('notes'),
            ]);

            $this->syncDetails($opname, $request->input('details'));
        });

        return $this->successResponse(
            new StockOpnameResource($opname->load('details.stockUnit')),
            'Stock opname created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid): JsonResponse
    {
        $opname = StockOpname::where('uid', $uid)->firstOrFail();
        $previousStatus = $opname->status;
        $newStatus = $request->input('status');

        DB::transaction(function () use ($opname, $request, $previousStatus, $newStatus) {
            $opname->update([
                'opname_date' => $request->input('opname_date'),
                'notes' => $request->input('notes'),
                'status' => $newStatus,
            ]);

            // Only replace details when still in Draft (not yet approved/counted)
            if ($previousStatus === 'Draft') {
                $opname->details()->delete();
                $this->syncDetails($opname, $request->input('details'));
            }

            // On first approval: auto-create a StockAdjustment from opname difference_qty
            if ($newStatus === 'Approved' && $previousStatus !== 'Approved') {
                $opname->load('details');

                $rowsWithDiff = $opname->details->filter(
                    fn ($d) => $d->difference_qty !== null && $d->difference_qty != 0
                );

                if ($rowsWithDiff->isNotEmpty()) {
                    $adjustment = StockAdjustment::create([
                        'adjustment_date'     => now()->toDateString(),
                        'stock_opname_id'     => $opname->id,
                        'stock_opname_number' => $opname->opname_number,
                        'notes'               => "Auto-created from Stock Opname {$opname->opname_number}",
                    ]);

                    foreach ($rowsWithDiff as $detail) {
                        StockAdjustmentDetail::create([
                            'uid'                 => (string) Str::uuid(),
                            'stock_adjustment_id' => $adjustment->id,
                            'stock_unit_id'       => $detail->stock_unit_id,
                            'item_id'             => $detail->item_id,
                            'item_name'           => $detail->item_name,
                            'unit_id'             => $detail->unit_id,
                            'unit_symbol'         => $detail->unit_symbol,
                            'warehouse_id'        => $detail->warehouse_id,
                            'warehouse_name'      => $detail->warehouse_name,
                            'rack_id'             => $detail->rack_id,
                            'rack_name'           => $detail->rack_name,
                            'adjustment_qty'      => $detail->difference_qty,
                            'notes'               => null,
                        ]);
                    }
                }
            }
        });

        return $this->successResponse(
            new StockOpnameResource($opname->load('details.stockUnit')),
            'Stock opname updated successfully'
        );
    }

    public function enterCount(EnterCountRequest $request, string $uid): JsonResponse
    {
        $opname = StockOpname::where('uid', $uid)->firstOrFail();

        DB::transaction(function () use ($opname, $request) {
            foreach ($request->input('details') as $row) {
                $detail = StockOpnameDetail::where('uid', $row['uid'])
                    ->where('stock_opname_id', $opname->id)
                    ->firstOrFail();

                $actualQty = (float) $row['actual_qty'];

                $detail->update([
                    'actual_qty' => $actualQty,
                    'difference_qty' => $actualQty - (float) $detail->system_qty,
                    'notes' => $row['notes'] ?? $detail->notes,
                ]);
            }

            $opname->update(['status' => 'Waiting Approval']);
        });

        return $this->successResponse(
            new StockOpnameResource($opname->load('details.stockUnit')),
            'Count results saved successfully'
        );
    }

    public function revise(string $uid): JsonResponse
    {
        $opname = StockOpname::where('uid', $uid)->firstOrFail();

        abort_if($opname->status !== 'Rejected', 422, 'Only Rejected stock opnames can be revised.');

        $opname->update(['status' => 'Revised']);

        return $this->successResponse(
            new StockOpnameResource($opname->load('details.stockUnit')),
            'Stock opname revised successfully'
        );
    }

    public function destroy(string $uid): JsonResponse
    {
        $opname = StockOpname::where('uid', $uid)->firstOrFail();

        abort_if(
            ! in_array($opname->status, ['Draft', 'Revised']),
            422,
            'Only Draft or Revised stock opnames can be deleted.'
        );

        $opname->details()->delete();
        $opname->delete();

        return $this->successResponse(null, 'Stock opname deleted successfully');
    }

    /**
     * Create detail rows by resolving each stock_unit_uid and snapshotting system_qty.
     */
    private function syncDetails(StockOpname $opname, array $details): void
    {
        foreach ($details as $row) {
            $stockUnit = StockUnits::with('stock')
                ->where('uid', $row['stock_unit_uid'])
                ->firstOrFail();

            $stock = $stockUnit->stock;

            StockOpnameDetail::create([
                'uid' => (string) Str::uuid(),
                'stock_opname_id' => $opname->id,
                'stock_unit_id' => $stockUnit->id,
                'item_id' => $stock->item_id,
                'item_name' => $stock->item_name,
                'unit_id' => $stock->unit_id,
                'unit_symbol' => $stock->unit_symbol,
                'warehouse_id' => $stock->warehouse_id,
                'warehouse_name' => $stock->warehouse_name,
                'rack_id' => $stock->rack_id,
                'rack_name' => $stock->rack_name,
                'system_qty' => (float) $stockUnit->qty,
                'notes' => $row['notes'] ?? null,
            ]);
        }
    }
}
