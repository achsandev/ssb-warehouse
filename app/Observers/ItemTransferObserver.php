<?php

namespace App\Observers;

use App\Models\ItemTransfer;
use Illuminate\Support\Facades\Auth;

class ItemTransferObserver
{
    public function creating(ItemTransfer $itemTransfer): void
    {
        if (Auth::check()) {
            $itemTransfer->created_by_id = Auth::id();
            $itemTransfer->created_by_name = Auth::user()->name;
        }
    }

    public function updating(ItemTransfer $itemTransfer): void
    {
        if (Auth::check()) {
            $itemTransfer->updated_by_id = Auth::id();
            $itemTransfer->updated_by_name = Auth::user()->name;
        }
    }
}
