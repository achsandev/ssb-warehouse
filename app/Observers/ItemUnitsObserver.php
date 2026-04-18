<?php

namespace App\Observers;

use App\Models\ItemUnits;
use Illuminate\Support\Facades\Auth;

class ItemUnitsObserver
{
    public function creating(ItemUnits $item_units)
    {
        if (Auth::check()) {
            $item_units->created_by_id = Auth::id();
            $item_units->created_by_name = Auth::user()->name;
        }
    }

    public function updating(ItemUnits $item_units)
    {
        if (Auth::check()) {
            $item_units->updated_by_id = Auth::id();
            $item_units->updated_by_name = Auth::user()->name;
        }
    }
}
