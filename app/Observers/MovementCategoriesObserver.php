<?php

namespace App\Observers;

use App\Models\MovementCategories;
use Illuminate\Support\Facades\Auth;

class MovementCategoriesObserver
{
    public function creating(MovementCategories $movement_categories)
    {
        if (Auth::check()) {
            $movement_categories->created_by_id = Auth::id();
            $movement_categories->created_by_name = Auth::user()->name;
        }
    }

    public function updating(MovementCategories $movement_categories)
    {
        if (Auth::check()) {
            $movement_categories->updated_by_id = Auth::id();
            $movement_categories->updated_by_name = Auth::user()->name;
        }
    }
}
