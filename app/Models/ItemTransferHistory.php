<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ItemTransferHistory extends Model
{
    public const ACTION_RELOCATED = 'relocated';

    public const ACTION_MERGED = 'merged';

    protected $table = 'wh_item_transfer_history';

    protected $hidden = [
        'id',
        'item_transfer_id',
        'item_transfer_detail_id',
        'item_id',
        'unit_id',
        'from_warehouse_id',
        'from_rack_id',
        'from_tank_id',
        'to_warehouse_id',
        'to_rack_id',
        'to_tank_id',
        'performed_by_id',
    ];

    protected $fillable = [
        'uid',
        'item_transfer_id',
        'item_transfer_detail_id',
        'item_id',
        'item_name',
        'unit_id',
        'unit_symbol',
        'qty',
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
        'action',
        'performed_by_id',
        'performed_by_name',
        'performed_at',
    ];

    protected $casts = [
        'qty'          => 'decimal:4',
        'performed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
            if (empty($model->performed_at)) {
                $model->performed_at = now();
            }
        });
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(ItemTransfer::class, 'item_transfer_id', 'id');
    }

    public function detail(): BelongsTo
    {
        return $this->belongsTo(ItemTransferDetail::class, 'item_transfer_detail_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Items::class, 'item_id', 'id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ItemUnits::class, 'unit_id', 'id');
    }
}
