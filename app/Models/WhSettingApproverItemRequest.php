<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WhSettingApproverItemRequest extends Model
{
    use HasFactory;

    protected $table = 'wh_setting_approver_item_request';

    protected $hidden = ['id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
        'requester_role_id',
        'requester_role_name',
        'approver_role_id',
        'approver_role_name',
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

    public function requesterRole()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'requester_role_id');
    }

    public function approverRole()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'approver_role_id');
    }

    public function itemRequests()
    {
        return $this->hasMany(ItemRequest::class, 'created_by_role_id', 'requester_role_id');
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
