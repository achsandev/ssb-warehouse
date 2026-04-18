<?php

namespace App\Observers;

use App\Models\StockOpname;
use Illuminate\Support\Facades\Auth;

class StockOpnameObserver
{
    public function creating(StockOpname $stockOpname): void
    {
        if (Auth::check()) {
            $stockOpname->created_by_id   = Auth::id();
            $stockOpname->created_by_name = Auth::user()->name;
        }
    }

    public function updating(StockOpname $stockOpname): void
    {
        if (Auth::check()) {
            $stockOpname->updated_by_id   = Auth::id();
            $stockOpname->updated_by_name = Auth::user()->name;
        }
    }
}
