<?php

namespace App\Observers;

use App\Models\ReturnItemDetail;
use Illuminate\Support\Facades\Auth;

class ReturnItemDetailObserver
{
    public function creating(ReturnItemDetail $returnItemDetail): void
    {
        if (Auth::check()) {
            $returnItemDetail->created_by_id = Auth::id();
            $returnItemDetail->created_by_name = Auth::user()->name;
        }
    }

    public function updating(ReturnItemDetail $returnItemDetail): void
    {
        if (Auth::check()) {
            $returnItemDetail->updated_by_id = Auth::id();
            $returnItemDetail->updated_by_name = Auth::user()->name;
        }
    }
}
