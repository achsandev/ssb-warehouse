<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Warehouse extends Model
{
    protected $table = 'wh_warehouse';

    protected $hidden = ['id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
        'name',
        'address',
        'additional_info',
        'cash_balance',
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

    public function rack()
    {
        return $this->hasMany(Rack::class, 'warehouse_id', 'id');
    }

    public function tank()
    {
        return $this->hasMany(Tank::class, 'warehouse_id', 'id');
    }

    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class, 'warehouse_id', 'id');
    }

    public function itemStocks(): HasMany
    {
        return $this->hasMany(ItemStock::class, 'warehouse_id', 'id');
    }

    public function receiveItems(): HasMany
    {
        return $this->hasMany(ReceiveItem::class, 'warehouse_id', 'id');
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
