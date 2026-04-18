<?php

namespace App\Observers;

use App\Models\ItemUsageDetail;
use Illuminate\Support\Facades\Auth;

class ItemUsageDetailObserver
{
    public function creating(ItemUsageDetail $itemUsageDetail): void
    {
        if (Auth::check()) {
            $itemUsageDetail->created_by_id = Auth::id();
            $itemUsageDetail->created_by_name = Auth::user()->name;
        }
    }

    public function updating(ItemUsageDetail $itemUsageDetail): void
    {
        if (Auth::check()) {
            $itemUsageDetail->updated_by_id = Auth::id();
            $itemUsageDetail->updated_by_name = Auth::user()->name;
        }
    }
}
