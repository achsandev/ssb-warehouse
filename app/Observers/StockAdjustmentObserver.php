<?php

namespace App\Observers;

use App\Models\StockAdjustment;
use Illuminate\Support\Facades\Auth;

class StockAdjustmentObserver
{
    public function creating(StockAdjustment $stockAdjustment): void
    {
        if (Auth::check()) {
            $stockAdjustment->created_by_id   = Auth::id();
            $stockAdjustment->created_by_name = Auth::user()->name;
        }
    }

    public function updating(StockAdjustment $stockAdjustment): void
    {
        if (Auth::check()) {
            $stockAdjustment->updated_by_id   = Auth::id();
            $stockAdjustment->updated_by_name = Auth::user()->name;
        }
    }
}
