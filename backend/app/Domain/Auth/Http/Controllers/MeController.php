<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Domain\Auth\Http\Resources\UserResource;
use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MeController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            return ResponseFormatter::success(new UserResource(auth()->user()));

        } catch (Throwable $e) {
            Log::error('auth.me_unexpected', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return ResponseFormatter::serverError();
        }
    }
}
