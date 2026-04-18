<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockAdjustment extends Model
{
    protected $table = 'wh_stock_adjustment';

    protected $hidden = ['id', 'stock_opname_id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
        'uid',
        'adjustment_number',
        'adjustment_date',
        'stock_opname_id',
        'stock_opname_number',
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

            if (empty($model->adjustment_number)) {
                $prefix = 'SA';
                $month  = date('m');
                $year   = date('Y');

                DB::transaction(function () use ($model, $prefix, $month, $year) {
                    $last = self::whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->where('adjustment_number', 'like', "{$prefix}.{$month}.{$year}.%")
                        ->lockForUpdate()
                        ->orderByDesc('id')
                        ->first();

                    $lastNumber = $last ? (int) substr($last->adjustment_number, -6) : 0;
                    $nextNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);

                    $model->adjustment_number = "{$prefix}.{$month}.{$year}.{$nextNumber}";
                });
            }

            if (empty($model->status)) {
                $model->status = 'Draft';
            }
        });
    }

    public function details(): HasMany
    {
        return $this->hasMany(StockAdjustmentDetail::class, 'stock_adjustment_id', 'id');
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id', 'id');
    }
}
