<?php

namespace App\Http\Controllers;

// use App\Http\Resources\StockResource;
use App\Http\Requests\StockUnits\StoreRequest;
use App\Http\Requests\StockUnits\UpdateRequest;
use App\Http\Resources\StockUnitsResource;
use App\Models\ItemUnits;
use App\Models\Stock;
use App\Models\StockUnits;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class StockUnitsController extends Controller
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
            'stock.item_name',
            'stock.warehouse_name',
            'stock.rack_name',
            'stock.tank_name',
            'qty',
            'unit.symbol',
            'created_at',
        ];

        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = StockUnits::with([
            'stock' => function ($stock) {
                $stock->select('id', 'uid', 'item_name', 'warehouse_name', 'rack_name', 'tank_name');
            },
            'unit' => function ($unit) {
                $unit->select('id', 'uid', 'name', 'symbol');
            },
        ]);

        if ($search) {
            $query->where(function ($q) {
                $q->where('qty', 'like', "{$search}%");
            });

            $q->orWhereHas('stock', function ($sub) use ($search) {
                $sub->where('item_name', 'like', "{$search}%")
                    ->orWhere('warehouse_name', 'like', "{$search}%")
                    ->orWhere('rack_name', 'like', "{$search}%")
                    ->orWhere('tank_name', 'like', "{$search}%");
            });

            $q->orWhereHas('unit', function ($sub) use ($search) {
                $sub->where('symbol', 'like', "{$search}%");
            });
        }

        if (in_array($sort_by, ['item_name', 'warehouse_name', 'rack_name', 'tank_name'])) {
            $query->join('stocks as s', 's.id', '=', 'stock_units.stock_id')
                ->orderBy("s.$sort_by", $sort_dir);
        } elseif ($sort_by === 'symbol') {
            $query->join('units as u', 'u.id', '=', 'stock_units.unit_id')
                ->orderBy('u.symbol', $sort_dir);
        } else {
            $query->orderBy($sort_by, $sort_dir);
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'id',
                    'uid',
                    'stock_id',
                    'unit_id',
                    'qty',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                StockUnitsResource::collection($data),
                'List of stock unit'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'id',
                    'uid',
                    'stock_id',
                    'unit_id',
                    'qty',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(
            StockUnitsResource::collection($data),
            'List of stock unit'
        );
    }

    public function getByStockUid(string $uid)
    {
        $stock_data = Stock::select('id')->where('uid', $uid)->firstOrFail();

        $query = StockUnits::with([
            'stock' => function ($stock) {
                $stock->select('id', 'uid', 'item_name', 'warehouse_name', 'rack_name', 'tank_name');
            },
            'unit' => function ($unit) {
                $unit->select('id', 'uid', 'name', 'symbol');
            },
        ]);

        $data = $query
            ->get([
                'id',
                'uid',
                'stock_id',
                'unit_id',
                'qty',
                'created_at',
                'updated_at',
                'created_by_name',
                'updated_by_name',
            ])
            ->where('stock_id', $stock_data->id);

        return $this->successResponse(
            StockUnitsResource::collection($data),
            'List of stock unit'
        );
    }

    public function store(StoreRequest $request)
    {
        $message = '';
        $stock = Stock::select('id', 'item_id')->where('uid', $request->input('stock_uid'))->firstOrFail();
        $base_unit = ItemUnits::select('id')->where('uid', $request->input('base_unit_uid'))->firstOrFail();
        $derived_unit = ItemUnits::select('id')->where('uid', $request->input('derived_unit_uid'))->firstOrFail();
        $stock_unit = StockUnits::select('id', 'stock_id', 'unit_id', 'qty')->where([['stock_id', '=', $stock->id], ['unit_id', '=', $base_unit->id]])->firstOrFail();
        $convert_qty = $request->input('convert_qty');
        $converted_qty = $request->input('converted_qty');
        $check_conversion = StockUnits::select('id', 'qty')->where([['stock_id', '=', $stock->id], ['unit_id', '=', $derived_unit->id]])->first();

        $data = $request->validated();

        $stock_unit->update([
            'stock_id' => $stock_unit->stock_id,
            'item_id' => $stock->item_id,
            'unit_id' => $stock_unit->unit_id,
            'qty' => ($stock_unit->qty - $convert_qty),
        ]);

        if ($check_conversion) {
            $data = array_merge($data, [
                'stock_id' => $stock->id,
                'item_id' => $stock->item_id,
                'unit_id' => $derived_unit->id,
                'qty' => ($converted_qty + $check_conversion->qty),
            ]);

            $check_conversion->update($data);
            $message = 'Stock updated successfully';

            return $this->successResponse(
                new StockUnitsResource($check_conversion),
                $message,
                201
            );
        } else {
            $data = array_merge($data, [
                'stock_id' => $stock->id,
                'item_id' => $stock->item_id,
                'unit_id' => $derived_unit->id,
                'qty' => $converted_qty,
            ]);

            $stock_unit_create = StockUnits::create($data);
            $message = 'Stock created successfully';

            return $this->successResponse(
                new StockUnitsResource($stock_unit_create),
                $message,
                201
            );
        }

    }

    public function update(UpdateRequest $request, string $uid)
    {
        $stockUnit = StockUnits::where('uid', $uid)->first();
        if (! $stockUnit) {
            return $this->errorResponse('Stock unit not found', null, 404);
        }

        $data = $request->validated();
        $stockUnit->update($data);

        return $this->successResponse(
            new StockUnitsResource($stockUnit),
            'Stock unit updated successfully',
            200
        );
    }

    public function destroy(string $uid)
    {
        $stockUnit = StockUnits::where('uid', $uid)->first();
        if (! $stockUnit) {
            return $this->errorResponse('Stock unit not found', null, 404);
        }

        $stockUnit->delete();

        return $this->successResponse(
            null,
            'Stock unit deleted successfully',
            200
        );
    }

    // public function update(StoreRequest $request, string $uid)
    // {
    //     $stock = Stock::where('uid', $uid)->firstOrFail();
    //     $item = Items::select('id', 'name', 'unit_id', 'unit_symbol')->where('uid', $request->input('item_uid'))->firstOrFail();
    //     $warehouse = Warehouse::select('id', 'name')->where('uid', $request->input('warehouse_uid'))->firstOrFail();

    //     $rack = null;
    //     if ($request->filled('rack_uid')) {
    //         $rack = Rack::select('id', 'name')->where('uid', $request->input('rack_uid'))->firstOrFail();
    //     }

    //     $tank = null;
    //     if ($request->filled('tank_uid')) {
    //         $tank = Tank::select('id', 'name')->where('uid', $request->input('tank_uid'))->firstOrFail();
    //     }

    //     $qty = $request->input('qty');

    //     $data = $request->validated();

    //     $data = array_merge($data, [
    //         'item_id' => $item->id,
    //         'item_name' => $item->name,
    //         'warehouse_id' => $warehouse->id,
    //         'warehouse_name' => $warehouse->name,
    //         'rack_id' => $rack?->id,
    //         'rack_name' => $rack?->name,
    //         'tank_id' => $tank?->id,
    //         'tank_name' => $tank?->name,
    //         'qty' => $qty,
    //         'unit_id' => $item->unit_id,
    //         'unit_symbol' => $item->unit_symbol,
    //     ]);

    //     $stock->update($data);

    //     StockUnits::updateOrCreate([
    //         'stock_id' => $stock->id,
    //         'unit_id' => $item->unit_id,
    //     ],
    //         [
    //             'qty' => $qty,
    //         ]);

    //     return $this->successResponse(
    //         new StockResource($stock),
    //         'Stock updated successfully',
    //         201
    //     );
    // }

    // public function destroy(string $uid)
    // {
    //     $stock = Stock::where('uid', $uid)->first();

    //     if (! $stock) {
    //         return $this->errorResponse('Stock not found', null, 404);
    //     }

    //     $stock->delete();

    //     return $this->successResponse(
    //         null,
    //         'Stock deleted successfully',
    //         200
    //     );
    // }
}
