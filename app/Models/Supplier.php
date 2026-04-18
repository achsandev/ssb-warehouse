<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Supplier extends Model
{
    protected $table = 'wh_supplier';

    protected $hidden = ['id', 'payment_method_id', 'payment_duration_id', 'tax_type_id'];

    protected $fillable = [
        'name',
        'address',
        'phone_number',
        'npwp_number',
        'pic_name',
        'email',
        'payment_method_id',
        'payment_method_name',
        'payment_duration_id',
        'payment_duration_name',
        'tax_type_id',
        'tax_type',
        'additional_info',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }

            $model->updated_at = null;
        });
    }

    public function item(): HasMany
    {
        return $this->hasMany(Items::class, 'supplier_id', 'id');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Items::class, 'wh_items_suppliers', 'supplier_id', 'item_id');
    }

    public function payment_method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethods::class, 'payment_method_id', 'id');
    }

    public function payment_duration(): BelongsTo
    {
        return $this->belongsTo(PaymentDuration::class, 'payment_duration_id', 'id');
    }

    public function tax_types(): BelongsToMany
    {
        return $this->belongsToMany(TaxTypes::class, 'wh_suppliers_tax_types', 'supplier_id', 'tax_type_id');
    }

    public function purchase_order_detail(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'supplier_id', 'id');
    }

    public function receive_item_detail(): HasMany
    {
        return $this->hasMany(ReceiveItemDetail::class, 'supplier_id', 'id');
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
