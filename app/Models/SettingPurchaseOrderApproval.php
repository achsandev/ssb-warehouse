<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SettingPurchaseOrderApproval extends Model
{
    protected $table = 'wh_setting_purchase_order_approval';

    protected $hidden = [
        'id',
        'created_by_id',
        'updated_by_id',
    ];

    protected $fillable = [
        'uid',
        'role_id',
        'role_name',
        'level',
        'created_by_name',
        'updated_by_name',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

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
}
