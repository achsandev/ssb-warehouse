<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StockOpnameDetail extends Model
{
    protected $table = 'wh_stock_opname_detail';

    protected $hidden = [
        'id',
        'stock_opname_id',
        'stock_unit_id',
        'item_id',
        'unit_id',
        'warehouse_id',
        'rack_id',
        'created_by_id',
        'updated_by_id',
    ];

    protected $fillable = [
        'stock_opname_id',
        'stock_unit_id',
        'item_id',
        'item_name',
        'unit_id',
        'unit_symbol',
        'warehouse_id',
        'warehouse_name',
        'rack_id',
        'rack_name',
        'system_qty',
        'actual_qty',
        'difference_qty',
        'notes',
        'created_by_id',
        'created_by_name',
        'updated_by_id',
        'updated_by_name',
    ];

    protected $casts = [
        'system_qty'     => 'float',
        'actual_qty'     => 'float',
        'difference_qty' => 'float',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }

            $model->updated_at = null;
        });
    }

    public function setUpdatedAt($value)
    {
        if (! $this->exists) {
            return $this;
        }

        $this->attributes['updated_at'] = $value;

        return $this;
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id', 'id');
    }

    public function stockUnit(): BelongsTo
    {
        return $this->belongsTo(StockUnits::class, 'stock_unit_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Items::class, 'item_id', 'id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ItemUnits::class, 'unit_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }
}
