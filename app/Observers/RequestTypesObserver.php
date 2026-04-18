<?php

namespace App\Observers;

use App\Models\RequestTypes;
use Illuminate\Support\Facades\Auth;

class RequestTypesObserver
{
    public function creating(RequestTypes $request_types)
    {
        if (Auth::check()) {
            $request_types->created_by_id = Auth::id();
            $request_types->created_by_name = Auth::user()->name;
        }
    }

    public function updating(RequestTypes $request_types)
    {
        if (Auth::check()) {
            $request_types->updated_by_id = Auth::id();
            $request_types->updated_by_name = Auth::user()->name;
        }
    }
}
