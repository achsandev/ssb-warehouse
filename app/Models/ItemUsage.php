<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ItemUsage extends Model
{
    protected $table = 'wh_item_usage';

    protected $hidden = ['id', 'item_request_id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
        'item_request_id',
        'usage_date',
        'usage_number',
        'project_name',
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

            if (empty($model->usage_number)) {
                $prefix = 'OI';
                $month = date('m');
                $year = date('Y');

                DB::transaction(function () use ($model, $prefix, $month, $year) {
                    $last = self::whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->where('usage_number', 'like', "{$prefix}.{$month}.{$year}.%")
                        ->lockForUpdate()
                        ->orderByDesc('id')
                        ->first();

                    $lastNumber = $last ? (int) substr($last->usage_number, -6) : 0;
                    $nextNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);

                    $model->usage_number = "{$prefix}.{$month}.{$year}.{$nextNumber}";
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

    public function itemRequest(): BelongsTo
    {
        return $this->belongsTo(ItemRequest::class, 'item_request_id', 'id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ItemUsageDetail::class, 'item_usage_id', 'id');
    }
}
