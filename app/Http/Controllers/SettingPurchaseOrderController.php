<?php

namespace App\Http\Controllers;

use App\Http\Resources\SettingPurchaseOrderApprovalResource;
use App\Models\SettingPurchaseOrderApproval;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SettingPurchaseOrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $page = $request->integer('page', 1);
        $per_page = $request->integer('per_page', 10);
        $sort_by = $request->string('sort_by', 'created_at')->toString();
        $sort_dir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowed_sort = [
            'role_name',
            'level',
            'created_at',
        ];

        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = SettingPurchaseOrderApproval::query();

        if ($search) {
            $query->where('role_name', 'like', "{$search}%");
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'id',
                    'uid',
                    'role_name',
                    'level',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                SettingPurchaseOrderApprovalResource::collection($data),
                'List of setting purchase order approvals'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'id',
                    'uid',
                    'role_name',
                    'level',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(
            SettingPurchaseOrderApprovalResource::collection($data),
            'List of setting purchase order approvals'
        );
    }
}
