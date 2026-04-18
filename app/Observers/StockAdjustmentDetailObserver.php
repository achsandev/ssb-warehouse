<?php

namespace App\Observers;

use App\Models\StockAdjustmentDetail;
use Illuminate\Support\Facades\Auth;

class StockAdjustmentDetailObserver
{
    public function creating(StockAdjustmentDetail $stockAdjustmentDetail): void
    {
        if (Auth::check()) {
            $stockAdjustmentDetail->created_by_id   = Auth::id();
            $stockAdjustmentDetail->created_by_name = Auth::user()->name;
        }
    }

    public function updating(StockAdjustmentDetail $stockAdjustmentDetail): void
    {
        if (Auth::check()) {
            $stockAdjustmentDetail->updated_by_id   = Auth::id();
            $stockAdjustmentDetail->updated_by_name = Auth::user()->name;
        }
    }
}
