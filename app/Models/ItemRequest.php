<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ItemRequest extends Model
{
    protected $table = 'wh_item_request';

    protected $hidden = ['id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
        'requirement',
        'request_date',
        'unit_code',
        'wo_number',
        'warehouse_id',
        'is_project',
        'project_name',
        'department_name',
        'status',
        'reject_reason',
        'created_by_role_id',
    ];

    protected $casts = [
        'is_project' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }

            if (empty($model->request_number)) {
                $prefix = 'PB';
                $month = date('m');
                $year = date('Y');

                DB::transaction(function () use ($model, $prefix, $month, $year) {
                    $lastReceipt = self::whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->where('request_number', 'like', "{$prefix}.{$month}.{$year}.%")
                        ->lockForUpdate()
                        ->orderByDesc('id')
                        ->first();

                    $lastNumber = 0;

                    if ($lastReceipt) {
                        $lastNumber = (int) substr($lastReceipt->request_number, -6);
                    }

                    $nextNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);

                    $model->request_number = "{$prefix}.{$month}.{$year}.{$nextNumber}";
                });
            }

            if (empty($model->status)) {
                $model->status = 'Waiting Approval';
            }

            $model->updated_at = null;
        });
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function item_request_detail(): HasMany
    {
        return $this->hasMany(ItemRequestDetail::class, 'item_request_id', 'id');
    }

    // Get the approver setting for this request based on the creator's role
    public function approverSetting()
    {
        return $this->hasOne(WhSettingApproverItemRequest::class, 'requester_role_id', 'created_by_role_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'item_request_id', 'id');
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
