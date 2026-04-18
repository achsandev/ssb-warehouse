<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PoApprovalLog extends Model
{
    protected $table = 'wh_po_approval_log';

    protected $hidden = ['id', 'purchase_order_id', 'approved_by_id'];

    protected $fillable = [
        'purchase_order_id',
        'approval_level',
        'role_name',
        'status',
        'notes',
        'approved_by_id',
        'approved_by_name',
    ];

    protected $casts = [
        'approval_level' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    public function purchase_order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by_id', 'id');
    }
}
