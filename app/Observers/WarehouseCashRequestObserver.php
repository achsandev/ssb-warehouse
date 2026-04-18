<?php

namespace App\Observers;

use App\Models\WarehouseCashRequest;
use Illuminate\Support\Facades\Auth;

class WarehouseCashRequestObserver
{
    public function creating(WarehouseCashRequest $model): void
    {
        if (Auth::check()) {
            $model->created_by_id   = Auth::id();
            $model->created_by_name = Auth::user()->name;
        }
    }

    public function updating(WarehouseCashRequest $model): void
    {
        if (Auth::check()) {
            $model->updated_by_id   = Auth::id();
            $model->updated_by_name = Auth::user()->name;
        }
    }
}
