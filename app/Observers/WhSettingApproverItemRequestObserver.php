<?php

namespace App\Observers;

use App\Models\WhSettingApproverItemRequest;
use Illuminate\Support\Facades\Auth;

class WhSettingApproverItemRequestObserver
{
    public function creating(WhSettingApproverItemRequest $request_types)
    {
        if (Auth::check()) {
            $request_types->created_by_id = Auth::id();
            $request_types->created_by_name = Auth::user()->name;
        }
    }

    public function updating(WhSettingApproverItemRequest $request_types)
    {
        if (Auth::check()) {
            $request_types->updated_by_id = Auth::id();
            $request_types->updated_by_name = Auth::user()->name;
        }
    }
}
