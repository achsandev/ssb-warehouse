<?php

namespace App\Observers;

use App\Models\CashPayment;
use Illuminate\Support\Facades\Auth;

class CashPaymentObserver
{
    public function creating(CashPayment $cashPayment): void
    {
        if (Auth::check()) {
            $cashPayment->created_by_id   = Auth::id();
            $cashPayment->created_by_name = Auth::user()->name;
        }
    }

    public function updating(CashPayment $cashPayment): void
    {
        if (Auth::check()) {
            $cashPayment->updated_by_id   = Auth::id();
            $cashPayment->updated_by_name = Auth::user()->name;
        }
    }
}
