<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'Thành công', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    protected function error(string $message = 'Có lỗi xảy ra', int $status = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    protected function paginated($resource, string $message = 'Thành công'): JsonResponse
    {
        if ($resource instanceof \Illuminate\Http\Resources\Json\AnonymousResourceCollection) {
            $paginator = $resource->resource;
            return response()->json([
                'success' => true,
                'message' => $message,
                'data'    => $resource,
                'meta'    => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ]);
        }

        if ($resource instanceof LengthAwarePaginator) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data'    => $resource->items(),
                'meta'    => [
                    'current_page' => $resource->currentPage(),
                    'last_page'    => $resource->lastPage(),
                    'per_page'     => $resource->perPage(),
                    'total'        => $resource->total(),
                ],
            ]);
        }

        return $this->success($resource, $message);
    }
}
