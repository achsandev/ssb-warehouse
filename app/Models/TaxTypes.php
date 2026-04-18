<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class TaxTypes extends Model
{
    protected $table = 'wh_tax_types';

    protected $hidden = ['id'];

    public const FORMULA_TYPES = ['formula', 'percentage', 'manual'];

    protected $fillable = [
        'uid',
        'name',
        'description',
        'formula_type',
        'formula',
        'uses_dpp',
    ];

    protected $casts = [
        'uses_dpp' => 'boolean',
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

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'wh_suppliers_tax_types', 'tax_type_id', 'supplier_id');
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
