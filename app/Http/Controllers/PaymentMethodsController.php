<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentMethods\StoreRequest;
use App\Http\Requests\PaymentMethods\UpdateRequest;
use App\Http\Resources\PaymentMethodsResource;
use App\Models\PaymentMethods;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PaymentMethodsController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $page = $request->integer('page', 1);
        $per_page = $request->integer('per_page', 10);
        $sort_by = $request->string('sort_by', 'created_at')->toString();
        $sort_dir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowed_sort = ['id', 'name', 'created_at'];
        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = PaymentMethods::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'id',
                    'uid',
                    'name',
                    'description',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                PaymentMethodsResource::collection($data),
                'List of payment methods'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'id',
                    'uid',
                    'name',
                    'description',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(
            PaymentMethodsResource::collection($data),
            'List of payment methods'
        );
    }

    public function store(StoreRequest $request)
    {
        $brand = PaymentMethods::create($request->validated());

        return $this->successResponse(
            new PaymentMethodsResource($brand),
            'Payment methods created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid)
    {
        $item_brands = PaymentMethods::where('uid', $uid)->firstOrFail();

        $item_brands->update($request->validated());

        return $this->successResponse(
            new PaymentMethodsResource($item_brands),
            'Payment methods updated successfully'
        );
    }

    public function destroy(string $uid)
    {
        $item_brands = PaymentMethods::where('uid', $uid)->first();

        if (! $item_brands) {
            return $this->errorResponse('Payment methods not found', null, 404);
        }

        $item_brands->delete();

        return $this->successResponse(
            null,
            'Payment methods deleted successfully',
            200
        );
    }
}
