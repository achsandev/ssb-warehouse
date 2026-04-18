<?php

namespace App\Observers;

use App\Models\ItemCategories;
use Illuminate\Support\Facades\Auth;

class ItemCategoriesObserver
{
    public function creating(ItemCategories $item_categories)
    {
        if (Auth::check()) {
            $item_categories->created_by_id = Auth::id();
            $item_categories->created_by_name = Auth::user()->name;
        }
    }

    public function updating(ItemCategories $item_categories)
    {
        if (Auth::check()) {
            $item_categories->updated_by_id = Auth::id();
            $item_categories->updated_by_name = Auth::user()->name;
        }
    }
}
