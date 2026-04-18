<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ItemRequestDetail extends Model
{
    protected $table = 'wh_item_request_detail';

    protected $hidden = ['id', 'item_request_id', 'item_id', 'unit_id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
        'item_request_id',
        'item_id',
        'unit_id',
        'qty',
        'description',
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

    public function item_request(): BelongsTo
    {
        return $this->belongsTo(ItemRequest::class, 'item_request_id', 'id');
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
