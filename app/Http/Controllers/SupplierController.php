<?php

namespace App\Http\Controllers;

use App\Http\Requests\Supplier\StoreRequest;
use App\Http\Requests\Supplier\UpdateRequest;
use App\Http\Resources\SupplierResource;
use App\Models\PaymentDuration;
use App\Models\PaymentMethods;
use App\Models\Supplier;
use App\Models\TaxTypes;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
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
            'name',
            'address',
            'phone_number',
            'npwp_number',
            'pic_name',
            'email',
            'payment_method_name',
            'payment_duration_name',
            'created_at',
        ];

        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = Supplier::with([
            'payment_method' => function ($payment_method) {
                $payment_method->select('id', 'uid', 'name');
            },
            'payment_duration' => function ($payment_duration) {
                $payment_duration->select('id', 'uid', 'name');
            },
            'tax_types' => function ($tax_types) {
                $tax_types->select('id', 'uid', 'name');
            },
        ]);

        if ($search) {
            $query->where('name', 'like', "{$search}%")
                ->orWhere('address', 'like', "{$search}%")
                ->orWhere('phone_number', 'like', "{$search}%")
                ->orWhere('npwp_number', 'like', "{$search}%")
                ->orWhere('pic_name', 'like', "{$search}%")
                ->orWhere('email', 'like', "{$search}%");
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
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

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
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
                ],
                'page', $page
            );

        return $this->successResponse(
            SupplierResource::collection($data),
            'List of supplier'
        );
    }

    public function store(StoreRequest $request)
    {
        $payment_method = PaymentMethods::select('id', 'name')->where('uid', $request->input('payment_method_uid'))->firstOrFail();
        $payment_duration = PaymentDuration::select('id', 'name')->where('uid', $request->input('payment_duration_uid'))->firstOrFail();

        $tax_types = TaxTypes::select('id', 'name')->whereIn('uid', $request->input('tax_type_uid'))->pluck('id')->toArray();

        $data = $request->validated();
        unset(
            $data['payment_method_uid'],
            $data['payment_duration_uid'],
            $data['tax_type_uid']
        );
        $data['payment_method_id'] = $payment_method->id;
        $data['payment_method_name'] = $payment_method->name;
        $data['payment_duration_id'] = $payment_duration->id;
        $data['payment_duration_name'] = $payment_duration->name;

        $supplier = Supplier::create($data);

        if (! empty($tax_types)) {
            $supplier->tax_types()->attach($tax_types);
        }

        return $this->successResponse(
            new SupplierResource($supplier),
            'Supplier created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid)
    {
        $payment_method = null;
        $payment_duration = null;
        $tax_types = [];

        $supplier = Supplier::where('uid', $uid)->firstOrFail();

        if ($request->filled('payment_method_uid')) {
            $payment_method = PaymentMethods::select('id', 'name')
                ->where('uid', $request->input('payment_method_uid'))
                ->firstOrFail();
        }

        if ($request->filled('payment_duration_uid')) {
            $payment_duration = PaymentDuration::select('id', 'name')
                ->where('uid', $request->input('payment_duration_uid'))
                ->firstOrFail();
        }

        if ($request->filled('tax_type_uid')) {
            $tax_types = TaxTypes::select('id', 'name')
                ->whereIn('uid', $request->input('tax_type_uid'))
                ->pluck('id')
                ->toArray();
        }

        $data = $request->validated();
        unset(
            $data['payment_method_uid'],
            $data['payment_duration_uid'],
            $data['tax_type_uid']
        );

        if ($payment_method) {
            $data['payment_method_id'] = $payment_method->id;
            $data['payment_method_name'] = $payment_method->name;
        }

        if ($payment_duration) {
            $data['payment_duration_id'] = $payment_duration->id;
            $data['payment_duration_name'] = $payment_duration->name;
        }

        $supplier->update($data);

        if (! empty($tax_types)) {
            $supplier->tax_types()->sync($tax_types);
        }

        return $this->successResponse(
            new SupplierResource($supplier->fresh()),
            'Supplier updated successfully'
        );
    }

    public function destroy(string $uid)
    {
        $item_brands = Supplier::where('uid', $uid)->first();

        if (! $item_brands) {
            return $this->errorResponse('Supplier not found', null, 404);
        }

        $item_brands->delete();

        return $this->successResponse(
            null,
            'Supplier deleted successfully',
            200
        );
    }
}
