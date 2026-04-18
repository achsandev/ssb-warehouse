<?php

namespace App\Observers;

use App\Models\UsageUnits;
use Illuminate\Support\Facades\Auth;

class UsageUnitsObserver
{
    public function creating(UsageUnits $usage_units)
    {
        if (Auth::check()) {
            $usage_units->created_by_id = Auth::id();
            $usage_units->created_by_name = Auth::user()->name;
        }
    }

    public function updating(UsageUnits $usage_units)
    {
        if (Auth::check()) {
            $usage_units->updated_by_id = Auth::id();
            $usage_units->updated_by_name = Auth::user()->name;
        }
    }
}
