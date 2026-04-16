<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Domain\Auth\Application\TokenService;
use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogoutController
{
    public function __invoke(Request $request, TokenService $tokenService): JsonResponse
    {
        try {
            $tokenService->revoke($request->input('refresh_token', ''));

            Log::info('auth.logout', ['user_id' => auth()->id()]);

            return ResponseFormatter::noContent();

        } catch (Throwable $e) {
            Log::error('auth.logout_unexpected', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return ResponseFormatter::serverError();
        }
    }
}
