<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockOpname extends Model
{
    protected $table = 'wh_stock_opname';

    protected $hidden = ['id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
        'uid',
        'opname_number',
        'opname_date',
        'notes',
        'status',
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

            if (empty($model->opname_number)) {
                $prefix = 'SO';
                $month  = date('m');
                $year   = date('Y');

                DB::transaction(function () use ($model, $prefix, $month, $year) {
                    $last = self::whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->where('opname_number', 'like', "{$prefix}.{$month}.{$year}.%")
                        ->lockForUpdate()
                        ->orderByDesc('id')
                        ->first();

                    $lastNumber = $last ? (int) substr($last->opname_number, -6) : 0;
                    $nextNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);

                    $model->opname_number = "{$prefix}.{$month}.{$year}.{$nextNumber}";
                });
            }

            if (empty($model->status)) {
                $model->status = 'Draft';
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

    public function details(): HasMany
    {
        return $this->hasMany(StockOpnameDetail::class, 'stock_opname_id', 'id');
    }
}
