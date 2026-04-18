<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Traits\ApiResponse;

class SupplierLookupController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $query = Supplier::with([
            'payment_method' => function ($payment_method) {
                $payment_method->select('id', 'uid', 'name');
            },
            'payment_duration' => function ($payment_duration) {
                $payment_duration->select('id', 'uid', 'name');
            },
            'tax_types' => function ($tax_types) {
                $tax_types->select('id', 'uid', 'name', 'formula_type', 'formula', 'uses_dpp');
            },
        ]);

        $data = $query
            ->orderBy('name', 'asc')
            ->get([
                'id',
                'uid',
                'name',
                'address',
                'phone_number',
                'npwp_number',
                'pic_name',
                'email',
                'payment_method_id',
                'payment_duration_id',
                'additional_info',
                'created_at',
                'updated_at',
                'created_by_name',
                'updated_by_name',
            ]);

        return $this->successResponse(
            SupplierResource::collection($data),
            'List of supplier'
        );
    }

    public function show(string $uid)
    {
        $supplier = Supplier::with([
            'payment_method' => function ($payment_method) {
                $payment_method->select('id', 'uid', 'name');
            },
            'payment_duration' => function ($payment_duration) {
                $payment_duration->select('id', 'uid', 'name');
            },
            'tax_types' => function ($tax_types) {
                $tax_types->select('id', 'uid', 'name', 'formula_type', 'formula', 'uses_dpp');
            },
        ])->where('uid', $uid)->firstOrFail([
            'id',
            'uid',
            'name',
            'address',
            'phone_number',
            'npwp_number',
            'pic_name',
            'email',
            'payment_method_id',
            'payment_duration_id',
            'additional_info',
            'created_at',
            'updated_at',
            'created_by_name',
            'updated_by_name',
        ]);

        return $this->successResponse(
            new SupplierResource($supplier),
            'Supplier details'
        );
    }
}
