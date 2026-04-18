<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemsUnitTypes extends Model
{
    protected $table = 'wh_items_unit_types';

    public $timestamps = false;

    protected $hidden = [
        'item_id',
        'unit_type_id',
    ];

    protected $fillable = [
        'item_id',
        'item_name',
        'unit_type_id',
        'unit_type_name',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Items::class, 'item_id');
    }

    public function usage_unit(): BelongsTo
    {
        return $this->belongsTo(UsageUnits::class, 'unit_type_id');
    }
}
