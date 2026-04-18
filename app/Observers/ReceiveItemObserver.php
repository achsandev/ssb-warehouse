<?php

namespace App\Observers;

use App\Models\ReceiveItem;
use Illuminate\Support\Facades\Auth;

class ReceiveItemObserver
{
    public function creating(ReceiveItem $receiveItem)
    {
        if (Auth::check()) {
            $receiveItem->created_by_id = Auth::id();
            $receiveItem->created_by_name = Auth::user()->name;
        }
    }

    public function updating(ReceiveItem $receiveItem)
    {
        if (Auth::check()) {
            $receiveItem->updated_by_id = Auth::id();
            $receiveItem->updated_by_name = Auth::user()->name;
        }
    }
}
