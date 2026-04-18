<?php

namespace App\Observers;

use App\Models\Tank;
use Illuminate\Support\Facades\Auth;

class TankObserver
{
    public function creating(Tank $tank)
    {
        if (Auth::check()) {
            $tank->created_by_id = Auth::id();
            $tank->created_by_name = Auth::user()->name;
        }
    }

    public function updating(Tank $tank)
    {
        if (Auth::check()) {
            $tank->updated_by_id = Auth::id();
            $tank->updated_by_name = Auth::user()->name;
        }
    }
}
