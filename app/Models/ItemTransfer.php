<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ItemTransfer extends Model
{
    protected $table = 'wh_item_transfer';

    protected $hidden = [
        'id',
        'from_warehouse_id', 'from_rack_id', 'from_tank_id',
        'to_warehouse_id', 'to_rack_id', 'to_tank_id',
        'parent_transfer_id',
        'created_by_id', 'updated_by_id', 'approved_by_id',
    ];

    protected $fillable = [
        'uid',
        'transfer_number',
        'transfer_date',
        'from_warehouse_id',
        'from_warehouse_name',
        'from_rack_id',
        'from_rack_name',
        'from_tank_id',
        'from_tank_name',
        'to_warehouse_id',
        'to_warehouse_name',
        'to_rack_id',
        'to_rack_name',
        'to_tank_id',
        'to_tank_name',
        'notes',
        'status',
        'reject_notes',
        'parent_transfer_id',
        'has_pending_displacement',
        'approved_by_id',
        'approved_by_name',
        'approved_at',
        'cancelled_at',
        'created_by_id',
        'created_by_name',
        'updated_by_id',
        'updated_by_name',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'has_pending_displacement' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }

            if (empty($model->transfer_number)) {
                $prefix = 'TB';
                $month = date('m');
                $year = date('Y');

                DB::transaction(function () use ($model, $prefix, $month, $year) {
                    $last = self::whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->where('transfer_number', 'like', "{$prefix}.{$month}.{$year}.%")
                        ->lockForUpdate()
                        ->orderByDesc('id')
                        ->first();

                    $lastNumber = $last ? (int) substr($last->transfer_number, -6) : 0;
                    $nextNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);

                    $model->transfer_number = "{$prefix}.{$month}.{$year}.{$nextNumber}";
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

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id', 'id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id', 'id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ItemTransferDetail::class, 'item_transfer_id', 'id');
    }

    public function fromRack(): BelongsTo
    {
        return $this->belongsTo(Rack::class, 'from_rack_id', 'id');
    }

    public function fromTank(): BelongsTo
    {
        return $this->belongsTo(Tank::class, 'from_tank_id', 'id');
    }

    public function toRack(): BelongsTo
    {
        return $this->belongsTo(Rack::class, 'to_rack_id', 'id');
    }

    public function toTank(): BelongsTo
    {
        return $this->belongsTo(Tank::class, 'to_tank_id', 'id');
    }

    public function parentTransfer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_transfer_id', 'id');
    }

    public function childTransfers(): HasMany
    {
        return $this->hasMany(self::class, 'parent_transfer_id', 'id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ItemTransferLog::class, 'item_transfer_id', 'id')
            ->orderBy('created_at', 'asc');
    }
}
