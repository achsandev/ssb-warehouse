<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentDuration\StoreRequest;
use App\Http\Requests\PaymentDuration\UpdateRequest;
use App\Http\Resources\PaymentDurationResource;
use App\Models\PaymentDuration;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PaymentDurationController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $page = $request->integer('page', 1);
        $per_page = $request->integer('per_page', 10);
        $sort_by = $request->string('sort_by', 'created_at')->toString();
        $sort_dir = $request->string('sort_dir', 'desc')->toString();
        $search = $request->string('search')->toString();

        $allowed_sort = ['name', 'created_at'];
        if (! in_array($sort_by, $allowed_sort)) {
            $sort_by = 'created_at';
        }

        $query = PaymentDuration::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($per_page === -1) {
            $data = $query
                ->orderBy($sort_by, $sort_dir)
                ->get([
                    'uid',
                    'name',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ]);

            return $this->successResponse(
                PaymentDurationResource::collection($data),
                'List of payment duration'
            );
        }

        $data = $query
            ->orderBy($sort_by, $sort_dir)
            ->paginate(
                $per_page, [
                    'uid',
                    'name',
                    'created_at',
                    'updated_at',
                    'created_by_name',
                    'updated_by_name',
                ],
                'page', $page
            );

        return $this->successResponse(
            PaymentDurationResource::collection($data),
            'List of payment duration'
        );
    }

    public function store(StoreRequest $request)
    {
        $usage_units = PaymentDuration::create($request->validated());

        return $this->successResponse(
            new PaymentDurationResource($usage_units),
            'Payment duration created successfully',
            201
        );
    }

    public function update(UpdateRequest $request, string $uid)
    {
        $usage_units = PaymentDuration::where('uid', $uid)->firstOrFail();

        $usage_units->update($request->validated());

        return $this->successResponse(
            new PaymentDurationResource($usage_units),
            'Payment duration updated successfully'
        );
    }

    public function destroy(string $uid)
    {
        $usage_units = PaymentDuration::where('uid', $uid)->first();

        if (! $usage_units) {
            return $this->errorResponse('Payment duration not found', null, 404);
        }

        $usage_units->delete();

        return $this->successResponse(
            null,
            'Payment duration deleted successfully',
            200
        );
    }
}
