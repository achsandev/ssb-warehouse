<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserDetail extends Model
{
    use HasFactory;

    protected $table = 'user_detail';

    protected $hidden = ['id', 'user_id', 'id_karyawan', 'direct_supervisor_id'];

    protected $fillable = [
        'user_id',
        'id_karyawan',
        'nik',
        'name',
        'department',
        'sub_department',
        'position',
        'direct_supervisor_id',
        'direct_supervisor_position',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function directSupervisor()
    {
        return $this->belongsTo(User::class, 'direct_supervisor_id');
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
