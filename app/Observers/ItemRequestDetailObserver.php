<?php

namespace App\Observers;

use App\Models\ItemRequestDetail;
use Illuminate\Support\Facades\Auth;

class ItemRequestDetailObserver
{
    public function creating(ItemRequestDetail $item_request_detail)
    {
        if (Auth::check()) {
            $item_request_detail->created_by_id = Auth::id();
            $item_request_detail->created_by_name = Auth::user()->name;
        }
    }

    public function updating(ItemRequestDetail $item_request_detail)
    {
        if (Auth::check()) {
            $item_request_detail->updated_by_id = Auth::id();
            $item_request_detail->updated_by_name = Auth::user()->name;
        }
    }
}
