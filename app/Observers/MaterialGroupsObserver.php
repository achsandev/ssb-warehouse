<?php

namespace App\Observers;

use App\Models\MaterialGroups;
use Illuminate\Support\Facades\Auth;

class MaterialGroupsObserver
{
    public function creating(MaterialGroups $material_groups)
    {
        if (Auth::check()) {
            $material_groups->created_by_id = Auth::id();
            $material_groups->created_by_name = Auth::user()->name;
        }
    }

    public function updating(MaterialGroups $material_groups)
    {
        if (Auth::check()) {
            $material_groups->updated_by_id = Auth::id();
            $material_groups->updated_by_name = Auth::user()->name;
        }
    }
}
