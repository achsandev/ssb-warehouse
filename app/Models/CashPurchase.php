<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashPurchase extends Model
{
    protected $table = 'wh_cash_purchase';

    protected $hidden = ['id', 'warehouse_id', 'po_id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
        'uid',
        'purchase_number',
        'purchase_date',
        'warehouse_id',
        'warehouse_name',
        'po_id',
        'po_number',
        'po_total_amount',
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

            if (empty($model->purchase_number)) {
                $prefix = 'CP';
                $month  = date('m');
                $year   = date('Y');

                DB::transaction(function () use ($model, $prefix, $month, $year) {
                    $last = self::whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->where('purchase_number', 'like', "{$prefix}.{$month}.{$year}.%")
                        ->lockForUpdate()
                        ->orderByDesc('id')
                        ->first();

                    $lastNumber = $last ? (int) substr($last->purchase_number, -6) : 0;
                    $nextNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);

                    $model->purchase_number = "{$prefix}.{$month}.{$year}.{$nextNumber}";
                });
            }

            if (empty($model->status)) {
                $model->status = 'Waiting Approval';
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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id', 'id');
    }
}
