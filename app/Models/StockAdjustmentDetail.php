<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StockAdjustmentDetail extends Model
{
    protected $table = 'wh_stock_adjustment_detail';

    protected $hidden = [
        'id',
        'stock_adjustment_id',
        'stock_unit_id',
        'item_id',
        'unit_id',
        'warehouse_id',
        'rack_id',
        'created_by_id',
        'updated_by_id',
    ];

    protected $fillable = [
        'uid',
        'stock_adjustment_id',
        'stock_unit_id',
        'item_id',
        'item_name',
        'unit_id',
        'unit_symbol',
        'warehouse_id',
        'warehouse_name',
        'rack_id',
        'rack_name',
        'adjustment_qty',
        'notes',
        'created_by_id',
        'created_by_name',
        'updated_by_id',
        'updated_by_name',
    ];

    protected $casts = [
        'adjustment_qty' => 'float',
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

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id', 'id');
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
