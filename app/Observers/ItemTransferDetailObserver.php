<?php

namespace App\Observers;

use App\Models\ItemTransferDetail;
use Illuminate\Support\Facades\Auth;

class ItemTransferDetailObserver
{
    public function creating(ItemTransferDetail $itemTransferDetail): void
    {
        if (Auth::check()) {
            $itemTransferDetail->created_by_id = Auth::id();
            $itemTransferDetail->created_by_name = Auth::user()->name;
        }
    }

    public function updating(ItemTransferDetail $itemTransferDetail): void
    {
        if (Auth::check()) {
            $itemTransferDetail->updated_by_id = Auth::id();
            $itemTransferDetail->updated_by_name = Auth::user()->name;
        }
    }
}
