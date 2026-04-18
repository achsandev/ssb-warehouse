<?php

namespace App\Observers;

use App\Models\ValuationMethods;
use Illuminate\Support\Facades\Auth;

class ValuationMethodsObserver
{
    public function creating(ValuationMethods $valuation_methods)
    {
        if (Auth::check()) {
            $valuation_methods->created_by_id = Auth::id();
            $valuation_methods->created_by_name = Auth::user()->name;
        }
    }

    public function updating(ValuationMethods $valuation_methods)
    {
        if (Auth::check()) {
            $valuation_methods->updated_by_id = Auth::id();
            $valuation_methods->updated_by_name = Auth::user()->name;
        }
    }
}
