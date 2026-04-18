<?php

namespace App\Observers;

use App\Models\ItemRequest;
use Illuminate\Support\Facades\Auth;

class ItemRequestObserver
{
    public function creating(ItemRequest $item_request)
    {
        if (Auth::check()) {
            $item_request->created_by_id = Auth::id();
            $item_request->created_by_name = Auth::user()->name;
        }
    }

    public function updating(ItemRequest $item_request)
    {
        if (Auth::check()) {
            $item_request->updated_by_id = Auth::id();
            $item_request->updated_by_name = Auth::user()->name;
        }
    }
}
