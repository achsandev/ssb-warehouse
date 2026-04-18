<?php

namespace App\Observers;

use App\Models\PaymentMethods;
use Illuminate\Support\Facades\Auth;

class PaymentMethodsObserver
{
    public function creating(PaymentMethods $payment_methods)
    {
        if (Auth::check()) {
            $payment_methods->created_by_id = Auth::id();
            $payment_methods->created_by_name = Auth::user()->name;
        }
    }

    public function updating(PaymentMethods $payment_methods)
    {
        if (Auth::check()) {
            $payment_methods->updated_by_id = Auth::id();
            $payment_methods->updated_by_name = Auth::user()->name;
        }
    }
}
