<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Stock extends Model
{
    protected $table = 'wh_stocks';

    protected $hidden = ['id', 'item_id', 'warehouse_id', 'rack_id', 'tank_id', 'unit_id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
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
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }

            $model->updated_at = null;
        });
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

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class, 'rack_id', 'id');
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class, 'tank_id', 'id');
    }

    public function stock_units(): HasMany
    {
        return $this->hasMany(StockUnits::class, 'stock_id', 'id');
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
