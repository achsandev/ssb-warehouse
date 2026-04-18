<?php

namespace App\Observers;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderObserver
{
    public function creating(PurchaseOrder $purchase_order)
    {
        if (Auth::check()) {
            $purchase_order->created_by_id = Auth::id();
            $purchase_order->created_by_name = Auth::user()->name;
        }
    }

    public function updating(PurchaseOrder $purchase_order)
    {
        if (Auth::check()) {
            $purchase_order->updated_by_id = Auth::id();
            $purchase_order->updated_by_name = Auth::user()->name;
        }
    }
}
