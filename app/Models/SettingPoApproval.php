<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SettingPoApproval extends Model
{
    protected $table = 'wh_setting_po_approval';

    protected $hidden = ['id', 'created_by_id', 'updated_by_id'];

    protected $fillable = [
        'level',
        'role_id',
        'min_amount',
        'description',
        'is_active',
        'created_by_id',
        'created_by_name',
        'updated_by_id',
        'updated_by_name',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_amount' => 'decimal:2',
        'level' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
            $model->updated_at = null;
        });
    }

    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function setUpdatedAt($value)
    {
        if (! $this->exists) {
            return $this;
        }
        $this->attributes['updated_at'] = $value;

        return $this;
    }

    /**
     * Get all active approval levels required for a given total amount.
     *
     * A level is required when:
     *   - min_amount IS NULL  → always required
     *   - min_amount IS SET   → required only when total_amount >= min_amount
     */
    public static function getRequiredLevels(float|int $totalAmount): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->where(function ($q) use ($totalAmount) {
                $q->whereNull('min_amount')
                    ->orWhere('min_amount', '<=', $totalAmount);
            })
            ->orderBy('level')
            ->get();
    }
}
