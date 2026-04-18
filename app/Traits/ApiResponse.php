<?php

namespace App\Traits;

use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponse
{
    protected function successResponse($data = null, $message = 'Success', $status = 200)
    {
        $options = config('app.debug') ? JSON_PRETTY_PRINT : 0;

        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ];

        if ($data instanceof ResourceCollection && method_exists($data->resource, 'toArray')) {
            $pagination = $data->resource->toArray(request());

            $response['meta'] = [
                'current_page' => $pagination['current_page'] ?? null,
                'per_page' => $pagination['per_page'] ?? null,
                'total' => $pagination['total'] ?? null,
                'last_page' => $pagination['last_page'] ?? null,
            ];
        }

        return response()->json($response, $status, [], JSON_PRETTY_PRINT);
    }

    protected function errorResponse($message = 'Error', $errors = null, $status = 400)
    {
        $options = config('app.debug') ? JSON_PRETTY_PRINT : 0;

        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status, [], $options);
    }
}
