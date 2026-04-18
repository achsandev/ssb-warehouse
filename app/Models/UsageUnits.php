<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class UsageUnits extends Model
{
    protected $table = 'wh_usage_units';

    protected $hidden = ['id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
        'name',
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

    public function item_unit_type(): HasMany
    {
        return $this->hasMany(ItemUnitTypes::class, 'unit_type_id');
    }

    public function item(): HasMany
    {
        return $this->hasMany(Items::class, 'unit_id', 'id');
    }

    public function purchase_order_detail(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'unit_id', 'id');
    }

    public function receive_item_detail(): HasMany
    {
        return $this->hasMany(ReceiveItemDetail::class, 'unit_id', 'id');
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
