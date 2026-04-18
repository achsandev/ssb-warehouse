<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SettingDppFormula extends Model
{
    protected $table = 'wh_setting_dpp_formula';

    protected $hidden = ['id'];

    protected $fillable = [
        'uid',
        'name',
        'formula',
        'description',
        'is_active',
        'created_by_id',
        'created_by_name',
        'updated_by_id',
        'updated_by_name',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
}
