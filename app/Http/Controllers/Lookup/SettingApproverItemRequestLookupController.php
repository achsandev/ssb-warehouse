<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Models\WhSettingApproverItemRequest;
use App\Traits\ApiResponse;

class SettingApproverItemRequestLookupController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $data = WhSettingApproverItemRequest::orderBy('id', 'asc')->get();

        return $this->successResponse($data, 'List of setting approver item request');
    }
}
