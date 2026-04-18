<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stock\StoreRequest;
use App\Http\Requests\Stock\UpdateRequest;
use App\Http\Resources\StockResource;
use App\Models\Items;
use App\Models\Rack;
use App\Models\Stock;
use App\Models\StockUnits;
use App\Models\Tank;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class StockController extends Controller
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
            'item_name',
            'warehouse_name',
            'rack_name',
            'tank_name',
            'qty',
            'unit_symbol',
            'created_at',
        ];

        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = Stock::with([
            'item' => function ($item) {
                $item->select('id', 'uid', 'name');
            },
            'unit' => function ($unit) {
                $unit->select('id', 'uid', 'name', 'symbol');
            },
            'warehouse' => function ($warehouse) {
                $warehouse->select('id', 'uid', 'name');
            },
            'rack' => function ($rack) {
                $rack->select('id', 'uid', 'name');
            },
            'tank' => function ($tank) {
                $tank->select('id', 'uid', 'name');
            },
            'stock_units' => function ($q) {
                $q->select('id', 'uid', 'stock_id', 'unit_id', 'qty')
                    ->with(['unit:id,uid,name,symbol']);
            },
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                    ->orWhere('warehouse_name', 'like', "%{$search}%")
                    ->orWhere('rack_name', 'like', "%{$search}%")
                    ->orWhere('tank_name', 'like', "%{$search}%")
                    ->orWhere('unit_symbol', 'like', "%{$search}%");
            });
        }

        // ─── Location filters (untuk transfer form) ─────────────────────────
        if ($warehouseUid = $request->string('warehouse_uid')->toString()) {
            $warehouse = \App\Models\Warehouse::select('id')->where('uid', $warehouseUid)->first();
            if ($warehouse) {
                $query->where('warehouse_id', $warehouse->id);
            }
        }

        // rack_uid: 'null' string berarti filter ke rack IS NULL (gudang tanpa rak)
        $rackParam = $request->string('rack_uid')->toString();
        if ($rackParam === 'null') {
            $query->whereNull('rack_id');
        } elseif ($rackParam) {
            $rack = \App\Models\Rack::select('id')->where('uid', $rackParam)->first();
            if ($rack) {
                $query->where('rack_id', $rack->id);
            }
        }

        $tankParam = $request->string('tank_uid')->toString();
        if ($tankParam === 'null') {
            $query->whereNull('tank_id');
        } elseif ($tankParam) {
            $tank = \App\Models\Tank::select('id')->where('uid', $tankParam)->first();
            if ($tank) {
                $query->where('tank_id', $tank->id);
            }
        }

        // Filter qty > 0 (hanya stok yang ada)
        if ($request->boolean('with_stock_only')) {
            $query->where('qty', '>', 0);
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'id',
                    'uid',
                    'item_id',
                    'item_name',
                    'unit_id',
                    'unit_name',
                    'unit_symbol',
                    'warehouse_id',
                    'warehouse_name',
                    'rack_id',
                    'rack_name',
                    'tank_id',
                    'tank_name',
                    'qty',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                StockResource::collection($data),
                'List of stock'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'id',
                    'uid',
                    'item_id',
                    'item_name',
                    'unit_id',
                    'unit_name',
                    'unit_symbol',
                    'warehouse_id',
                    'warehouse_name',
                    'rack_id',
                    'rack_name',
                    'tank_id',
                    'tank_name',
                    'qty',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(
            StockResource::collection($data),
            'List of stock'
        );
    }

    /**
     * Get stock by item_uid
     *
     * @param  string  $item_uid
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByItemUid($item_uid)
    {
        $item = Items::where('uid', $item_uid)->first();
        if (! $item) {
            return $this->errorResponse('Item not found', null, 404);
        }

        $stocks = Stock::with([
            'item' => function ($item) {
                $item->select('id', 'uid', 'name');
            },
            'warehouse' => function ($warehouse) {
                $warehouse->select('id', 'uid', 'name');
            },
            'rack' => function ($rack) {
                $rack->select('id', 'uid', 'name');
            },
            'tank' => function ($tank) {
                $tank->select('id', 'uid', 'name');
            },
            'stock_units' => function ($q) {
                $q->select('id', 'uid', 'stock_id', 'unit_id', 'qty')
                    ->with(['unit:id,uid,name,symbol']);
            },
        ])
            ->where('item_id', $item->id)
            ->get([
                'id',
                'uid',
                'item_id',
                'item_name',
                'warehouse_id',
                'warehouse_name',
                'rack_id',
                'rack_name',
                'tank_id',
                'tank_name',
                'qty',
                'unit_id',
                'unit_symbol',
                'created_at',
                'updated_at',
                'created_by_name',
                'updated_by_name',
            ]);

        return $this->successResponse(
            StockResource::collection($stocks),
            'Stock by item_uid'
        );
    }

    public function store(StoreRequest $request)
    {
        $item = Items::select('id', 'name', 'unit_id', 'unit_name', 'unit_symbol')->where('uid', $request->input('item_uid'))->firstOrFail();
        $warehouse = Warehouse::select('id', 'name')->where('uid', $request->input('warehouse_uid'))->firstOrFail();

        $rack = null;
        if ($request->filled('rack_uid')) {
            $rack = Rack::select('id', 'name')->where('uid', $request->input('rack_uid'))->firstOrFail();
        }

        $tank = null;
        if ($request->filled('tank_uid')) {
            $tank = Tank::select('id', 'name')->where('uid', $request->input('tank_uid'))->firstOrFail();
        }

        $qty = $request->input('qty');

        $data = $request->validated();

        $data = array_merge($data, [
            'item_id' => $item->id,
            'item_name' => $item->name,
            'warehouse_id' => $warehouse->id,
            'warehouse_name' => $warehouse->name,
            'rack_id' => $rack?->id,
            'rack_name' => $rack?->name,
            'tank_id' => $tank?->id,
            'tank_name' => $tank?->name,
            'qty' => $qty,
            'unit_id' => $item->unit_id,
            'unit_name' => $item->unit_name,
            'unit_symbol' => $item->unit_symbol,
        ]);

        $stock = Stock::create($data);

        StockUnits::create([
            'stock_id' => $stock->id,
            'item_id' => $item->id,
            'unit_id' => $item->unit_id,
            'qty' => $qty,
        ]);

        return $this->successResponse(
            new StockResource($stock),
            'Stock created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid)
    {
        $stock = Stock::where('uid', $uid)->firstOrFail();
        $item = Items::select('id', 'name', 'unit_id', 'unit_symbol')->where('uid', $request->input('item_uid'))->firstOrFail();
        $warehouse = Warehouse::select('id', 'name')->where('uid', $request->input('warehouse_uid'))->firstOrFail();

        $rack = null;
        if ($request->filled('rack_uid')) {
            $rack = Rack::select('id', 'name')->where('uid', $request->input('rack_uid'))->firstOrFail();
        }

        $tank = null;
        if ($request->filled('tank_uid')) {
            $tank = Tank::select('id', 'name')->where('uid', $request->input('tank_uid'))->firstOrFail();
        }

        $qty = $request->input('qty');

        $data = $request->validated();

        $data = array_merge($data, [
            'item_id' => $item->id,
            'item_name' => $item->name,
            'warehouse_id' => $warehouse->id,
            'warehouse_name' => $warehouse->name,
            'rack_id' => $rack?->id,
            'rack_name' => $rack?->name,
            'tank_id' => $tank?->id,
            'tank_name' => $tank?->name,
            'qty' => $qty,
            'unit_id' => $item->unit_id,
            'unit_symbol' => $item->unit_symbol,
        ]);

        $stock->update($data);

        StockUnits::updateOrCreate([
            'stock_id' => $stock->id,
            'item_id' => $item->id,
            'unit_id' => $item->unit_id,
        ],
            [
                'qty' => $qty,
            ]);

        return $this->successResponse(
            new StockResource($stock),
            'Stock updated successfully',
            201
        );
    }

    public function destroy(string $uid)
    {
        $stock = Stock::where('uid', $uid)->first();

        if (! $stock) {
            return $this->errorResponse('Stock not found', null, 404);
        }

        $stock->delete();

        return $this->successResponse(
            null,
            'Stock deleted successfully',
            200
        );
    }
}
