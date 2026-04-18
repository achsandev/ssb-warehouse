<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StockUnits extends Model
{
    protected $table = 'wh_stock_units';

    protected $hidden = ['id', 'stock_id', 'item_id', 'unit_id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
        'uid',
        'stock_id',
        'item_id',
        'unit_id',
        'qty',
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

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Items::class, 'item_id', 'id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ItemUnits::class, 'unit_id', 'id');
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
