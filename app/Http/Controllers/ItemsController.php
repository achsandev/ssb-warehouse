<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Http\Requests\Items\StoreRequest;
use App\Http\Requests\Items\UpdateRequest;
use App\Http\Resources\ItemsResource;
use App\Models\ItemBrands;
use App\Models\ItemCategories;
use App\Models\Items;
use App\Models\ItemsUnitTypes;
use App\Models\ItemUnits;
use App\Models\MaterialGroups;
use App\Models\MovementCategories;
use App\Models\RequestTypes;
use App\Models\SubMaterialGroups;
use App\Models\Supplier;
use App\Models\UsageUnits;
use App\Models\ValuationMethods;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ItemsController extends Controller
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
            'code',
            'name',
            'brand_name',
            'item_category_name',
            'unit_name',
            'min_qty',
            'movement_category_name',
            'valuation_method_name',
            'material_group_name',
            'sub_material_group_name',
            'supplier_name',
            'price',
            'created_at',
        ];

        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = Items::with([
            'brand' => function ($brand) {
                $brand->select('id', 'uid', 'name');
            },
            'category' => function ($category) {
                $category->select('id', 'uid', 'name');
            },
            'unit' => function ($unit) {
                $unit->select('id', 'uid', 'symbol', 'name');
            },
            'movement_category' => function ($movement_category) {
                $movement_category->select('id', 'uid', 'name');
            },
            'valuation_method' => function ($valuation_method) {
                $valuation_method->select('id', 'uid', 'name');
            },
            'material_group' => function ($material_group) {
                $material_group->select('id', 'uid', 'code', 'name');
            },
            'sub_material_group' => function ($sub_material_group) {
                $sub_material_group->select('id', 'uid', 'code', 'name');
            },
            'supplier' => function ($supplier) {
                $supplier->select('id', 'uid', 'name');
            },
            'request_types' => function ($request_types) {
                $request_types->select('id', 'uid', 'name');
            },
            'item_unit_type' => function ($item_unit_type) {
                $item_unit_type->select('item_id', 'unit_type_id')
                    ->with(['usage_unit' => function ($usage_unit) {
                        $usage_unit->select('id', 'uid', 'name');
                    }]);
            },
        ]);

        if ($search) {
            $query->where('code', 'like', "{$search}%")
                ->orWhere('name', 'like', "{$search}%")
                ->orWhere('brand_name', 'like', "{$search}%")
                ->orWhere('part_number', 'like', "{$search}%")
                ->orWhere('interchange_part', 'like', "{$search}%")
                ->orWhere('supplier_name', 'like', "{$search}%");
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'id',
                    'uid',
                    'code',
                    'name',
                    'brand_id',
                    'item_category_id',
                    'unit_id',
                    'min_qty',
                    'part_number',
                    'interchange_part',
                    'image',
                    'movement_category_id',
                    'valuation_method_id',
                    'material_group_id',
                    'sub_material_group_id',
                    'supplier_id',
                    'price',
                    'exp_date',
                    'additional_info',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                ItemsResource::collection($data),
                'List of item'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'id',
                    'uid',
                    'code',
                    'name',
                    'brand_id',
                    'item_category_id',
                    'unit_id',
                    'min_qty',
                    'part_number',
                    'interchange_part',
                    'image',
                    'movement_category_id',
                    'valuation_method_id',
                    'material_group_id',
                    'sub_material_group_id',
                    'supplier_id',
                    'price',
                    'exp_date',
                    'additional_info',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(
            ItemsResource::collection($data),
            'List of item'
        );
    }

    public function get_by_uid(string $uid)
    {
        $query = Items::with([
            'brand' => function ($brand) {
                $brand->select('id', 'uid', 'name');
            },
            'category' => function ($category) {
                $category->select('id', 'uid', 'name');
            },
            'unit' => function ($unit) {
                $unit->select('id', 'uid', 'symbol', 'name');
            },
            'movement_category' => function ($movement_category) {
                $movement_category->select('id', 'uid', 'name');
            },
            'valuation_method' => function ($valuation_method) {
                $valuation_method->select('id', 'uid', 'name');
            },
            'material_group' => function ($material_group) {
                $material_group->select('id', 'uid', 'code', 'name');
            },
            'sub_material_group' => function ($sub_material_group) {
                $sub_material_group->select('id', 'uid', 'code', 'name');
            },
            'supplier' => function ($supplier) {
                $supplier->select('id', 'uid', 'name');
            },
        ])->select([
            'uid',
            'code',
            'name',
            'brand_id',
            'item_category_id',
            'unit_id',
            'min_qty',
            'part_number',
            'interchange_part',
            'image',
            'movement_category_id',
            'valuation_method_id',
            'material_group_id',
            'sub_material_group_id',
            'supplier_id',
            'price',
            'exp_date',
            'additional_info',
            'created_at',
            'updated_at',
            'created_by_name',
            'updated_by_name',
        ])->where('uid', $uid);

        $data = $query->get();

        return $this->successResponse(
            ItemsResource::collection($data)
                ->map
                ->basicInfo(),
            'Data of item'
        );
    }

    public function store(StoreRequest $request)
    {
        $brand = ItemBrands::select('id', 'name')->where('uid', $request->input('brand_uid'))->firstOrFail();
        $category = ItemCategories::select('id', 'name')->where('uid', $request->input('category_uid'))->firstOrFail();
        $unit = ItemUnits::select('id', 'name', 'symbol')->where('uid', $request->input('unit_uid'))->firstOrFail();
        $movement_category = MovementCategories::select('id', 'name')->where('uid', $request->input('movement_category_uid'))->firstOrFail();
        $valuation_method = ValuationMethods::select('id', 'name')->where('uid', $request->input('valuation_method_uid'))->firstOrFail();
        $material_group = MaterialGroups::select('id', 'name')->where('uid', $request->input('material_group_uid'))->firstOrFail();
        $sub_material_group = SubMaterialGroups::select('id', 'name')->where('uid', $request->input('sub_material_group_uid'))->firstOrFail();
        $supplier = Supplier::select('id', 'name')->where('uid', $request->input('supplier_uid'))->firstOrFail();

        $request_types = RequestTypes::select('id', 'name')->whereIn('uid', $request->input('request_types_uid'))->pluck('id')->toArray();

        $data = $request->validated();

        $data['brand_id'] = $brand->id;
        $data['brand_name'] = $brand->name;
        $data['item_category_id'] = $category->id;
        $data['item_category_name'] = $category->name;
        $data['unit_id'] = $unit->id;
        $data['unit_symbol'] = $unit->symbol;
        $data['unit_name'] = $unit->name;
        $data['movement_category_id'] = $movement_category->id;
        $data['movement_category_name'] = $movement_category->name;
        $data['valuation_method_id'] = $valuation_method->id;
        $data['valuation_method_name'] = $valuation_method->name;
        $data['material_group_id'] = $material_group->id;
        $data['material_group_name'] = $material_group->name;
        $data['sub_material_group_id'] = $sub_material_group->id;
        $data['sub_material_group_name'] = $sub_material_group->name;
        $data['supplier_id'] = $supplier->id;
        $data['supplier_name'] = $supplier->name;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $file_helper = new FileHelper;
            $file_name = $file_helper->generateFileName($file);
            $path = $file->storeAs('items', $file_name, 'public');
            $data['image'] = $path;
        }

        $item = Items::create($data);

        if (! empty($request_types)) {
            // Pakai syncWithoutDetaching + de-dup supaya idempotent:
            // - kalau user double-submit, tidak akan melempar duplicate key error
            // - kalau input duplikat uid, hanya satu record yang di-insert
            $item->request_types()->syncWithoutDetaching(array_values(array_unique($request_types)));
        }

        // Dedup input uid dan pakai firstOrCreate supaya aman saat re-submit
        // atau payload mengandung uid duplikat (PK: item_id + unit_type_id).
        $unitTypeUids = array_values(array_unique($request->input('unit_types_uid', [])));
        foreach ($unitTypeUids as $unit_type_uid) {
            $usage_unit = UsageUnits::select('id', 'uid', 'name')->where('uid', $unit_type_uid)->firstOrFail();

            ItemsUnitTypes::firstOrCreate(
                [
                    'item_id'      => $item->id,
                    'unit_type_id' => $usage_unit->id,
                ],
                [
                    'item_name'      => $item->name,
                    'unit_type_name' => $usage_unit->name,
                ]
            );
        }

        return $this->successResponse(
            new ItemsResource($item),
            'Item created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid)
    {
        $item = Items::where('uid', $uid)->firstOrFail();

        $brand = ItemBrands::select('id', 'name')->where('uid', $request->input('brand_uid'))->firstOrFail();
        $category = ItemCategories::select('id', 'name')->where('uid', $request->input('category_uid'))->firstOrFail();
        $unit = ItemUnits::select('id', 'name', 'symbol')->where('uid', $request->input('unit_uid'))->firstOrFail();
        $movement_category = MovementCategories::select('id', 'name')->where('uid', $request->input('movement_category_uid'))->firstOrFail();
        $valuation_method = ValuationMethods::select('id', 'name')->where('uid', $request->input('valuation_method_uid'))->firstOrFail();
        $material_group = MaterialGroups::select('id', 'name')->where('uid', $request->input('material_group_uid'))->firstOrFail();
        $sub_material_group = SubMaterialGroups::select('id', 'name')->where('uid', $request->input('sub_material_group_uid'))->firstOrFail();
        $supplier = Supplier::select('id', 'name')->where('uid', $request->input('supplier_uid'))->firstOrFail();

        $request_types = RequestTypes::select('id', 'name')
            ->whereIn('uid', $request->input('request_types_uid', []))
            ->pluck('id')
            ->toArray();

        $data = $request->validated();

        $data['brand_id'] = $brand->id;
        $data['brand_name'] = $brand->name;
        $data['item_category_id'] = $category->id;
        $data['item_category_name'] = $category->name;
        $data['unit_id'] = $unit->id;
        $data['unit_symbol'] = $unit->symbol;
        $data['unit_name'] = $unit->name;
        $data['movement_category_id'] = $movement_category->id;
        $data['movement_category_name'] = $movement_category->name;
        $data['valuation_method_id'] = $valuation_method->id;
        $data['valuation_method_name'] = $valuation_method->name;
        $data['material_group_id'] = $material_group->id;
        $data['material_group_name'] = $material_group->name;
        $data['sub_material_group_id'] = $sub_material_group->id;
        $data['sub_material_group_name'] = $sub_material_group->name;
        $data['supplier_id'] = $supplier->id;
        $data['supplier_name'] = $supplier->name;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $file_helper = new FileHelper;
            $file_name = $file_helper->generateFileName($file);
            $path = $file->storeAs('items', $file_name, 'public');

            if ($item->image && Storage::disk('public')->exists($item->image)) {
                Storage::disk('public')->delete($item->image);
            }

            $data['image'] = $path;
        }

        $item->update($data);

        $item->request_types()->sync($request_types);

        if ($request->filled('unit_types_uid')) {
            $item->item_unit_type()->delete();

            foreach (array_values(array_unique($request->input('unit_types_uid'))) as $unit_type_uid) {
                $usage_unit = UsageUnits::select('id', 'uid', 'name')
                    ->where('uid', $unit_type_uid)
                    ->firstOrFail();

                ItemsUnitTypes::create([
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'unit_type_id' => $usage_unit->id,
                    'unit_type_name' => $usage_unit->name,
                ]);
            }
        }

        return $this->successResponse(
            new ItemsResource($item->fresh([
                'brand',
                'category',
                'unit',
                'movement_category',
                'valuation_method',
                'material_group',
                'sub_material_group',
                'supplier',
                'request_types',
                'item_unit_type.usage_unit',
            ])),
            'Item updated successfully'
        );
    }

    public function destroy(string $uid)
    {
        $item = Items::where('uid', $uid)->first();

        if (! $item) {
            return $this->errorResponse('Item not found', null, 404);
        }

        $item->delete();

        return $this->successResponse(
            null,
            'Item deleted successfully',
            200
        );
    }
}
