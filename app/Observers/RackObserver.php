<?php

namespace App\Observers;

use App\Models\Rack;
use Illuminate\Support\Facades\Auth;

class RackObserver
{
    public function creating(Rack $rack)
    {
        if (Auth::check()) {
            $rack->created_by_id = Auth::id();
            $rack->created_by_name = Auth::user()->name;
        }
    }

    public function updating(Rack $rack)
    {
        if (Auth::check()) {
            $rack->updated_by_id = Auth::id();
            $rack->updated_by_name = Auth::user()->name;
        }
    }
}
