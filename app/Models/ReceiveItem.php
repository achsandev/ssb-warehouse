<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReceiveItem extends Model
{
    protected $table = 'wh_receipt_item';

    protected $hidden = ['id', 'purchase_order_id', 'warehouse_id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
        'receipt_date',
        'project_name',
        'purchase_order_id',
        'warehouse_id',
        'shipping_cost',
        'status',
        'reject_reason',
        'additional_info',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }

            if (empty($model->receipt_number)) {
                $prefix = 'PI';
                $month = date('m');
                $year = date('Y');

                DB::transaction(function () use ($model, $prefix, $month, $year) {
                    $lastReceipt = self::whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->where('receipt_number', 'like', "{$prefix}.{$month}.{$year}.%")
                        ->lockForUpdate()
                        ->orderByDesc('id')
                        ->first();

                    $lastNumber = 0;

                    if ($lastReceipt) {
                        $lastNumber = (int) substr($lastReceipt->receipt_number, -6);
                    }

                    $nextNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);

                    $model->receipt_number = "{$prefix}.{$month}.{$year}.{$nextNumber}";
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

    public function purchase_order()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function details()
    {
        return $this->hasMany(ReceiveItemDetail::class, 'receipt_item_id', 'id');
    }
}
