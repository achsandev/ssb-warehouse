<?php

namespace App\Observers;

use App\Models\TaxTypes;
use Illuminate\Support\Facades\Auth;

class TaxTypesObserver
{
    public function creating(TaxTypes $tax_types)
    {
        if (Auth::check()) {
            $tax_types->created_by_id = Auth::id();
            $tax_types->created_by_name = Auth::user()->name;
        }
    }

    public function updating(TaxTypes $tax_types)
    {
        if (Auth::check()) {
            $tax_types->updated_by_id = Auth::id();
            $tax_types->updated_by_name = Auth::user()->name;
        }
    }
}
