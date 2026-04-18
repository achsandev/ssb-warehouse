<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ItemTransferLog extends Model
{
    protected $table = 'wh_item_transfer_logs';

    public $timestamps = false;

    protected $hidden = ['id', 'item_transfer_id', 'actor_id'];

    protected $fillable = [
        'uid',
        'item_transfer_id',
        'action',
        'from_status',
        'to_status',
        'notes',
        'metadata',
        'actor_id',
        'actor_name',
        'actor_role',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }

            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(ItemTransfer::class, 'item_transfer_id', 'id');
    }
}
