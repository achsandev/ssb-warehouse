<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SubMaterialGroups extends Model
{
    protected $table = 'wh_sub_material_groups';

    protected $hidden = ['id', 'material_group_id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
        'material_group_id',
        'material_group_name',
        'code',
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

    public function material_group()
    {
        return $this->belongsTo(MaterialGroups::class, 'material_group_id', 'id');
    }

    public function item(): HasMany
    {
        return $this->hasMany(Items::class, 'sub_material_group_id', 'id');
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
