<?php

namespace App\Observers;

use App\Models\UserDetail;
use Illuminate\Support\Facades\Auth;

class UserDetailObserver
{
    public function creating(UserDetail $userDetail)
    {
        if (Auth::check()) {
            $userDetail->created_by_id = Auth::id();
            $userDetail->created_by_name = Auth::user()->name;
        }
    }

    public function updating(UserDetail $userDetail)
    {
        if (Auth::check()) {
            $userDetail->updated_by_id = Auth::id();
            $userDetail->updated_by_name = Auth::user()->name;
        }
    }
}
