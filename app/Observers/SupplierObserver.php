<?php

namespace App\Observers;

use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;

class SupplierObserver
{
    public function creating(Supplier $supplier)
    {
        if (Auth::check()) {
            $supplier->created_by_id = Auth::id();
            $supplier->created_by_name = Auth::user()->name;
        }
    }

    public function updating(Supplier $supplier)
    {
        if (Auth::check()) {
            $supplier->updated_by_id = Auth::id();
            $supplier->updated_by_name = Auth::user()->name;
        }
    }
}
