<?php

namespace App\Observers;

use App\Models\ItemUsage;
use Illuminate\Support\Facades\Auth;

class ItemUsageObserver
{
    public function creating(ItemUsage $itemUsage): void
    {
        if (Auth::check()) {
            $itemUsage->created_by_id = Auth::id();
            $itemUsage->created_by_name = Auth::user()->name;
        }
    }

    public function updating(ItemUsage $itemUsage): void
    {
        if (Auth::check()) {
            $itemUsage->updated_by_id = Auth::id();
            $itemUsage->updated_by_name = Auth::user()->name;
        }
    }
}
