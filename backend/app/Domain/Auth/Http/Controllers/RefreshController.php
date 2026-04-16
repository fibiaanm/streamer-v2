<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Domain\Auth\Application\TokenService;
use App\Domain\Auth\Exceptions\RefreshTokenInvalidException;
use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshController
{
    public function __invoke(Request $request, TokenService $tokenService): JsonResponse
    {
        try {
            $tokens = $tokenService->refresh($request->input('refresh_token', ''));

            return ResponseFormatter::success($tokens);

        } catch (RefreshTokenInvalidException $e) {
            Log::warning('auth.refresh_invalid');
            return ResponseFormatter::error($e);

        } catch (Throwable $e) {
            Log::error('auth.refresh_unexpected', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return ResponseFormatter::serverError();
        }
    }
}
