<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ItemUnits extends Model
{
    protected $table = 'wh_item_units';

    protected $hidden = ['id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
        'uid',
        'name',
        'symbol',
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

    public function item(): HasMany
    {
        return $this->hasMany(Items::class, 'unit_id', 'id');
    }

    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class, 'unit_id', 'id');
    }

    public function stock_units(): HasMany
    {
        return $this->hasMany(StockUnits::class, 'unit_id', 'id');
    }

    public function item_request_detail(): HasMany
    {
        return $this->hasMany(ItemRequestDetail::class, 'unit_id', 'id');
    }

    public function purchase_order_detail(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'unit_id', 'id');
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
