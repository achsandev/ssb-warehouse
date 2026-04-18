<?php

namespace App\Observers;

use App\Models\StockOpnameDetail;
use Illuminate\Support\Facades\Auth;

class StockOpnameDetailObserver
{
    public function creating(StockOpnameDetail $stockOpnameDetail): void
    {
        if (Auth::check()) {
            $stockOpnameDetail->created_by_id   = Auth::id();
            $stockOpnameDetail->created_by_name = Auth::user()->name;
        }
    }

    public function updating(StockOpnameDetail $stockOpnameDetail): void
    {
        if (Auth::check()) {
            $stockOpnameDetail->updated_by_id   = Auth::id();
            $stockOpnameDetail->updated_by_name = Auth::user()->name;
        }
    }
}
