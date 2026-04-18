<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PoApprovalLog;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrder extends Model
{
    protected $table = 'wh_purchase_order';

    protected $hidden = ['id', 'item_request_id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
        'po_date',
        'project_name',
        'item_request_id',
        'total_amount',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }

            if (empty($model->po_number)) {
                $prefix = 'PO';
                $month = date('m');
                $year = date('Y');

                DB::transaction(function () use ($model, $prefix, $month, $year) {
                    $lastReceipt = self::whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->where('po_number', 'like', "{$prefix}.{$month}.{$year}.%")
                        ->lockForUpdate()
                        ->orderByDesc('id')
                        ->first();

                    $lastNumber = 0;

                    if ($lastReceipt) {
                        $lastNumber = (int) substr($lastReceipt->po_number, -6);
                    }

                    $nextNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);

                    $model->po_number = "{$prefix}.{$month}.{$year}.{$nextNumber}";
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

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'purchase_order_id', 'id');
    }

    public function item_request()
    {
        return $this->belongsTo(ItemRequest::class, 'item_request_id', 'id');
    }

    public function receive_items(): HasMany
    {
        return $this->hasMany(ReceiveItem::class, 'purchase_order_id', 'id');
    }

    public function approval_logs(): HasMany
    {
        return $this->hasMany(PoApprovalLog::class, 'purchase_order_id', 'id')
            ->orderBy('approval_level');
    }
}
