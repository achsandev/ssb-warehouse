<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReturnItemDetail extends Model
{
    protected $table = 'wh_return_item_detail';

    protected $hidden = ['id', 'return_item_id', 'item_id', 'unit_id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
        'return_item_id',
        'item_id',
        'unit_id',
        'qty',
        'return_qty',
        'description',
        'created_by_id',
        'created_by_name',
        'updated_by_id',
        'updated_by_name',
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

    public function returnItem(): BelongsTo
    {
        return $this->belongsTo(ReturnItem::class, 'return_item_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Items::class, 'item_id', 'id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ItemUnits::class, 'unit_id', 'id');
    }
}
