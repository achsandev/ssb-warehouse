<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RequestTypes extends Model
{
    protected $table = 'wh_request_types';

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

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Items::class, 'wh_items_request_types', 'request_type_id', 'item_id');
    }
}
