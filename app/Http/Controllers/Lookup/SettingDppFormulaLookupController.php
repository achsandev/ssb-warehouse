<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Http\Resources\SettingDppFormulaResource;
use App\Models\SettingDppFormula;
use App\Traits\ApiResponse;

class SettingDppFormulaLookupController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $data = SettingDppFormula::query()
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get([
                'id', 'uid', 'name', 'formula', 'description', 'is_active',
            ]);

        return $this->successResponse(
            SettingDppFormulaResource::collection($data),
            'List of active DPP formulas',
        );
    }
}
