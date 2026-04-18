<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Http\Resources\SettingPoApprovalResource;
use App\Models\SettingPoApproval;
use App\Traits\ApiResponse;

class SettingPoApprovalLookupController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $data = SettingPoApproval::with('role')
            ->orderBy('level', 'asc')
            ->get([
                'id',
                'uid',
                'level',
                'role_id',
                'min_amount',
                'description',
                'is_active',
                'created_at',
                'updated_at',
                'created_by_name',
                'updated_by_name',
            ]);

        return $this->successResponse(
            SettingPoApprovalResource::collection($data),
            'List of setting PO approval'
        );
    }
}
