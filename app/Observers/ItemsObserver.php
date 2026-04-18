<?php

namespace App\Observers;

use App\Models\Items;
use Illuminate\Support\Facades\Auth;

class ItemsObserver
{
    public function creating(Items $items)
    {
        if (Auth::check()) {
            $items->created_by_id = Auth::id();
            $items->created_by_name = Auth::user()->name;
        }
    }

    public function updating(Items $items)
    {
        if (Auth::check()) {
            $items->updated_by_id = Auth::id();
            $items->updated_by_name = Auth::user()->name;
        }
    }
}
