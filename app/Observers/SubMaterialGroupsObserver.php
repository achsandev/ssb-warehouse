<?php

namespace App\Observers;

use App\Models\SubMaterialGroups;
use Illuminate\Support\Facades\Auth;

class SubMaterialGroupsObserver
{
    public function creating(SubMaterialGroups $sub_material_groups)
    {
        if (Auth::check()) {
            $sub_material_groups->created_by_id = Auth::id();
            $sub_material_groups->created_by_name = Auth::user()->name;
        }
    }

    public function updating(SubMaterialGroups $sub_material_groups)
    {
        if (Auth::check()) {
            $sub_material_groups->updated_by_id = Auth::id();
            $sub_material_groups->updated_by_name = Auth::user()->name;
        }
    }
}
