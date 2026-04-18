<?php

namespace App\Observers;

use App\Models\CashPurchase;
use Illuminate\Support\Facades\Auth;

class CashPurchaseObserver
{
    public function creating(CashPurchase $cashPurchase): void
    {
        if (Auth::check()) {
            $cashPurchase->created_by_id   = Auth::id();
            $cashPurchase->created_by_name = Auth::user()->name;
        }
    }

    public function updating(CashPurchase $cashPurchase): void
    {
        if (Auth::check()) {
            $cashPurchase->updated_by_id   = Auth::id();
            $cashPurchase->updated_by_name = Auth::user()->name;
        }
    }
}
