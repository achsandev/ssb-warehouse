<?php

namespace App\Observers;

use App\Models\ReturnItem;
use Illuminate\Support\Facades\Auth;

class ReturnItemObserver
{
    public function creating(ReturnItem $returnItem): void
    {
        if (Auth::check()) {
            $returnItem->created_by_id = Auth::id();
            $returnItem->created_by_name = Auth::user()->name;
        }
    }

    public function updating(ReturnItem $returnItem): void
    {
        if (Auth::check()) {
            $returnItem->updated_by_id = Auth::id();
            $returnItem->updated_by_name = Auth::user()->name;
        }
    }
}
