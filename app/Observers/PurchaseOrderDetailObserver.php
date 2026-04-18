<?php

namespace App\Observers;

use App\Models\PurchaseOrderDetail;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderDetailObserver
{
    public function creating(PurchaseOrderDetail $purchase_order_detail)
    {
        if (Auth::check()) {
            $purchase_order_detail->created_by_id = Auth::id();
            $purchase_order_detail->created_by_name = Auth::user()->name;
        }
    }

    public function updating(PurchaseOrderDetail $purchase_order_detail)
    {
        if (Auth::check()) {
            $purchase_order_detail->updated_by_id = Auth::id();
            $purchase_order_detail->updated_by_name = Auth::user()->name;
        }
    }
}
