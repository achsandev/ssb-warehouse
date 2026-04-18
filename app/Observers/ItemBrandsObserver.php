<?php

namespace App\Observers;

use App\Models\ItemBrands;
use Illuminate\Support\Facades\Auth;

class ItemBrandsObserver
{
    public function creating(ItemBrands $item_brands)
    {
        if (Auth::check()) {
            $item_brands->created_by_id = Auth::id();
            $item_brands->created_by_name = Auth::user()->name;
        }
    }

    public function updating(ItemBrands $item_brands)
    {
        if (Auth::check()) {
            $item_brands->updated_by_id = Auth::id();
            $item_brands->updated_by_name = Auth::user()->name;
        }
    }
}
