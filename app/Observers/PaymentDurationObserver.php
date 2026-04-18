<?php

namespace App\Observers;

use App\Models\PaymentDuration;
use Illuminate\Support\Facades\Auth;

class PaymentDurationObserver
{
    public function creating(PaymentDuration $payment_duration)
    {
        if (Auth::check()) {
            $payment_duration->created_by_id = Auth::id();
            $payment_duration->created_by_name = Auth::user()->name;
        }
    }

    public function updating(PaymentDuration $payment_duration)
    {
        if (Auth::check()) {
            $payment_duration->updated_by_id = Auth::id();
            $payment_duration->updated_by_name = Auth::user()->name;
        }
    }
}
