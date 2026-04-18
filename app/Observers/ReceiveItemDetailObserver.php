<?php

namespace App\Observers;

use App\Models\ReceiveItemDetail;
use Illuminate\Support\Facades\Auth;

class ReceiveItemDetailObserver
{
    public function creating(ReceiveItemDetail $receiveItemDetail)
    {
        if (Auth::check()) {
            $receiveItemDetail->created_by_id = Auth::id();
            $receiveItemDetail->created_by_name = Auth::user()->name;
        }
    }

    public function updating(ReceiveItemDetail $receiveItemDetail)
    {
        if (Auth::check()) {
            $receiveItemDetail->updated_by_id = Auth::id();
            $receiveItemDetail->updated_by_name = Auth::user()->name;
        }
    }
}
