<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReceiveItemDetail extends Model
{
    protected $table = 'wh_receipt_item_detail';

    protected $hidden = [
        'id',
        'receipt_item_id',
        'item_id',
        'unit_id',
        'supplier_id',
        'created_by_id',
        'updated_by_id',
    ];

    protected $fillable = [
        'receipt_item_id',
        'item_id',
        'unit_id',
        'supplier_id',
        'qty',
        'price',
        'total',
        'qty_received',
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

    public function setUpdatedAt($value)
    {
        if (! $this->exists) {
            return $this;
        }

        $this->attributes['updated_at'] = $value;

        return $this;
    }

    public function receiveItem()
    {
        return $this->belongsTo(ReceiveItem::class, 'receipt_item_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Items::class, 'item_id', 'id');
    }

    public function unit()
    {
        return $this->belongsTo(ItemUnits::class, 'unit_id', 'id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }
}
