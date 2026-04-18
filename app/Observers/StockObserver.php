<?php

namespace App\Observers;

use App\Models\Stock;
use Illuminate\Support\Facades\Auth;

class StockObserver
{
    public function creating(Stock $stock)
    {
        if (Auth::check()) {
            $stock->created_by_id = Auth::id();
            $stock->created_by_name = Auth::user()->name;
        }
    }

    public function updating(Stock $stock)
    {
        if (Auth::check()) {
            $stock->updated_by_id = Auth::id();
            $stock->updated_by_name = Auth::user()->name;
        }
    }
}
