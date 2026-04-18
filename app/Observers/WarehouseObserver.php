<?php

namespace App\Observers;

use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;

class WarehouseObserver
{
    public function creating(Warehouse $warehouse)
    {
        if (Auth::check()) {
            $warehouse->created_by_id = Auth::id();
            $warehouse->created_by_name = Auth::user()->name;
        }
    }

    public function updating(Warehouse $warehouse)
    {
        if (Auth::check()) {
            $warehouse->updated_by_id = Auth::id();
            $warehouse->updated_by_name = Auth::user()->name;
        }
    }
}
