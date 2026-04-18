<?php

namespace App\Observers;

use App\Models\StockUnits;
use Illuminate\Support\Facades\Auth;

class StockUnitsObserver
{
    public function creating(StockUnits $stock_units)
    {
        if (Auth::check()) {
            $stock_units->created_by_id = Auth::id();
            $stock_units->created_by_name = Auth::user()->name;
        }
    }

    public function updating(StockUnits $stock_units)
    {
        if (Auth::check()) {
            $stock_units->updated_by_id = Auth::id();
            $stock_units->updated_by_name = Auth::user()->name;
        }
    }
}
