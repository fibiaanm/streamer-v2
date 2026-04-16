<?php

namespace App\Http\Formatters;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;

class ResponseFormatter
{
    public static function success(JsonResource|array $data, int $status = 200): JsonResponse
    {
        $payload = $data instanceof JsonResource ? $data->resolve() : $data;
        return response()->json(['data' => $payload], $status);
    }

    public static function created(JsonResource|array $data): JsonResponse
    {
        return static::success($data, 201);
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    public static function paginated(LengthAwarePaginator $paginator, string $resourceClass): JsonResponse
    {
        return response()->json([
            'data'  => $resourceClass::collection($paginator->getCollection())->resolve(),
            'meta'  => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
            'links' => [
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    public static function cursor(CursorPaginator $paginator, string $resourceClass): JsonResponse
    {
        return response()->json([
            'data' => $resourceClass::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'per_page'    => $paginator->perPage(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
            ],
        ]);
    }

    public static function error(AppException $e): JsonResponse
    {
        return response()->json([
            'error' => [
                'code'    => $e->getErrorCode()->value,
                'context' => $e->getContext(),
            ],
        ], $e->getHttpStatus());
    }

    public static function serverError(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code'    => ErrorCode::ServerError->value,
                'context' => [],
            ],
        ], 500);
    }
}
