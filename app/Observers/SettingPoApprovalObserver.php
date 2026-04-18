<?php

namespace App\Observers;

use App\Models\SettingPoApproval;
use Illuminate\Support\Facades\Auth;

class SettingPoApprovalObserver
{
    public function creating(SettingPoApproval $model): void
    {
        if (Auth::check()) {
            $model->created_by_id = Auth::id();
            $model->created_by_name = Auth::user()->name;
        }
    }

    public function updating(SettingPoApproval $model): void
    {
        if (Auth::check()) {
            $model->updated_by_id = Auth::id();
            $model->updated_by_name = Auth::user()->name;
        }
    }
}
