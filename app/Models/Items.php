<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Items extends Model
{
    protected $table = 'wh_items';

    protected $hidden = [
        'id',
        'brand_id',
        'item_category_id',
        'unit_id',
        'movement_category_id',
        'valuation_method_id',
        'material_group_id',
        'sub_material_group_id',
        'supplier_id',
        'created_by_id',
        'updated_by_id',
    ];

    protected $fillable = [
        'name',
        'brand_id',
        'brand_name',
        'item_category_id',
        'item_category_name',
        'unit_id',
        'unit_symbol',
        'unit_name',
        'min_qty',
        'part_number',
        'interchange_part',
        'image',
        'movement_category_id',
        'movement_category_name',
        'valuation_method_id',
        'valuation_method_name',
        'material_group_id',
        'material_group_name',
        'sub_material_group_id',
        'sub_material_group_name',
        'supplier_id',
        'supplier_name',
        'price',
        'exp_date',
        'additional_info',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }

            if (empty($model->code)) {
                $subGroup = SubMaterialGroups::select('id', 'code')
                    ->where('id', $model->sub_material_group_id)
                    ->firstOrFail();

                if (! $subGroup) {
                    throw new \Exception('Invalid sub material group');
                }

                DB::transaction(function () use ($model, $subGroup) {
                    $lastItem = self::where('sub_material_group_id', $model->sub_material_group_id)
                        ->lockForUpdate() // penting untuk mencegah race condition
                        ->orderByDesc('id')
                        ->first();

                    $lastNumber = 0;

                    if ($lastItem && isset($lastItem->code)) {
                        $lastNumber = intval(substr($lastItem->code, -4));
                    }

                    $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

                    $model->code = "{$subGroup->code}-{$nextNumber}";
                });
            }

            $model->updated_at = null;
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ItemBrands::class, 'brand_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategories::class, 'item_category_id', 'id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ItemUnits::class, 'unit_id', 'id');
    }

    public function movement_category(): BelongsTo
    {
        return $this->belongsTo(MovementCategories::class, 'movement_category_id', 'id');
    }

    public function valuation_method(): BelongsTo
    {
        return $this->belongsTo(ValuationMethods::class, 'valuation_method_id', 'id');
    }

    public function material_group(): BelongsTo
    {
        return $this->belongsTo(MaterialGroups::class, 'material_group_id', 'id');
    }

    public function sub_material_group(): BelongsTo
    {
        return $this->belongsTo(SubMaterialGroups::class, 'sub_material_group_id', 'id');
    }

    public function request_types(): BelongsToMany
    {
        return $this->belongsToMany(RequestTypes::class, 'wh_items_request_types', 'item_id', 'request_type_id');
    }

    public function item_unit_type(): HasMany
    {
        return $this->hasMany(ItemsUnitTypes::class, 'item_id')->select('item_id', 'item_name', 'unit_type_id', 'unit_type_name');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'wh_items_suppliers', 'item_id', 'supplier_id');
    }

    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class, 'item_id', 'id');
    }

    public function stock_units(): HasMany
    {
        return $this->hasMany(StockUnits::class, 'item_id', 'id');
    }

    public function item_request_detail(): HasMany
    {
        return $this->hasMany(ItemRequestDetail::class, 'item_id', 'id');
    }

    public function purchase_order_detail(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'item_id', 'id');
    }

    public function receive_item_detail(): HasMany
    {
        return $this->hasMany(ReceiveItemDetail::class, 'item_id', 'id');
    }

    public function setUpdatedAt($value)
    {
        if (! $this->exists) {
            return $this;
        }

        $this->attributes['updated_at'] = $value;

        return $this;
    }
}
