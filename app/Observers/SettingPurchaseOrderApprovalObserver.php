<?php

namespace App\Observers;

use App\Models\SettingPurchaseOrderApproval;
use Illuminate\Support\Facades\Auth;

class SettingPurchaseOrderApprovalObserver
{
    public function creating(SettingPurchaseOrderApproval $setting_purchase_order_approval)
    {
        if (Auth::check()) {
            $setting_purchase_order_approval->created_by_id = Auth::id();
            $setting_purchase_order_approval->created_by_name = Auth::user()->name;
        }
    }

    public function updating(SettingPurchaseOrderApproval $setting_purchase_order_approval)
    {
        if (Auth::check()) {
            $setting_purchase_order_approval->updated_by_id = Auth::id();
            $setting_purchase_order_approval->updated_by_name = Auth::user()->name;
        }
    }
}
